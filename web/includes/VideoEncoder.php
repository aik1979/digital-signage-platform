<?php
/**
 * VideoEncoder Class
 * Handles automatic video encoding to Raspberry Pi compatible format
 */

class VideoEncoder {
    private $ffmpegPath;
    private $ffprobePath;
    
    public function __construct() {
        // Try to find FFmpeg
        $this->ffmpegPath = $this->findExecutable('ffmpeg');
        $this->ffprobePath = $this->findExecutable('ffprobe');
    }
    
    /**
     * Find executable in system PATH
     */
    private function findExecutable($name) {
        $paths = [
            '/usr/bin/' . $name,
            '/usr/local/bin/' . $name,
            '/opt/bin/' . $name,
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }
        
        // Try which command
        $which = trim(shell_exec("which $name 2>/dev/null"));
        if (!empty($which) && file_exists($which)) {
            return $which;
        }
        
        return null;
    }
    
    /**
     * Check if FFmpeg is available
     */
    public function isAvailable() {
        return !empty($this->ffmpegPath);
    }
    
    /**
     * Get video information
     */
    public function getVideoInfo($inputPath) {
        if (!$this->ffprobePath) {
            return null;
        }
        
        $cmd = sprintf(
            '%s -v quiet -print_format json -show_format -show_streams %s 2>&1',
            escapeshellarg($this->ffprobePath),
            escapeshellarg($inputPath)
        );
        
        $output = shell_exec($cmd);
        return json_decode($output, true);
    }
    
    /**
     * Check if video needs encoding
     */
    public function needsEncoding($inputPath) {
        $info = $this->getVideoInfo($inputPath);
        if (!$info) {
            return true; // If we can't check, encode it to be safe
        }
        
        // Check video stream
        foreach ($info['streams'] as $stream) {
            if ($stream['codec_type'] === 'video') {
                $codec = strtolower($stream['codec_name']);
                $profile = isset($stream['profile']) ? strtolower($stream['profile']) : '';
                
                // Check if it's H.264 with baseline or main profile
                if ($codec === 'h264') {
                    if (strpos($profile, 'baseline') !== false || strpos($profile, 'main') !== false) {
                        return false; // Already compatible
                    }
                }
                
                // Any other codec needs encoding
                return true;
            }
        }
        
        return true;
    }
    
    /**
     * Encode video to Raspberry Pi compatible format
     * 
     * @param string $inputPath Input video file path
     * @param string $outputPath Output video file path
     * @return array ['success' => bool, 'message' => string, 'output' => string]
     */
    public function encode($inputPath, $outputPath) {
        if (!$this->isAvailable()) {
            return [
                'success' => false,
                'message' => 'FFmpeg is not installed on the server',
                'output' => ''
            ];
        }
        
        if (!file_exists($inputPath)) {
            return [
                'success' => false,
                'message' => 'Input file does not exist',
                'output' => ''
            ];
        }
        
        // FFmpeg command for Raspberry Pi compatible encoding
        // H.264 baseline profile, AAC audio, optimized for web playback
        $cmd = sprintf(
            '%s -i %s -c:v libx264 -profile:v baseline -level 3.0 -pix_fmt yuv420p ' .
            '-c:a aac -b:a 128k -ar 44100 ' .
            '-movflags +faststart ' .
            '-y %s 2>&1',
            escapeshellarg($this->ffmpegPath),
            escapeshellarg($inputPath),
            escapeshellarg($outputPath)
        );
        
        $output = shell_exec($cmd);
        
        // Check if output file was created
        if (file_exists($outputPath) && filesize($outputPath) > 0) {
            return [
                'success' => true,
                'message' => 'Video encoded successfully',
                'output' => $output
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Encoding failed',
                'output' => $output
            ];
        }
    }
    
    /**
     * Encode video in place (replaces original with encoded version)
     * 
     * @param string $filePath Path to video file
     * @return array ['success' => bool, 'message' => string]
     */
    public function encodeInPlace($filePath) {
        // Check if encoding is needed
        if (!$this->needsEncoding($filePath)) {
            return [
                'success' => true,
                'message' => 'Video is already in compatible format'
            ];
        }
        
        $tempPath = $filePath . '.encoding.mp4';
        
        // Encode to temporary file
        $result = $this->encode($filePath, $tempPath);
        
        if ($result['success']) {
            // Replace original with encoded version
            if (unlink($filePath) && rename($tempPath, $filePath)) {
                return [
                    'success' => true,
                    'message' => 'Video encoded and replaced successfully'
                ];
            } else {
                // Cleanup temp file
                if (file_exists($tempPath)) {
                    unlink($tempPath);
                }
                return [
                    'success' => false,
                    'message' => 'Failed to replace original file'
                ];
            }
        } else {
            // Cleanup temp file if it exists
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
            return $result;
        }
    }
}
?>
