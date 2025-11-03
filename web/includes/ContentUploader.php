<?php
/**
 * Content Upload Handler
 */
class ContentUploader {
    private $db;
    private $userId;
    
    public function __construct($db, $userId) {
        $this->db = $db;
        $this->userId = $userId;
    }
    
    /**
     * Upload and process a file
     */
    public function upload($file, $title = null, $duration = 10) {
        // Validate file
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'message' => 'No file uploaded'];
        }
        
        // Determine file type
        $mimeType = $file['type'];
        $fileType = $this->getFileType($mimeType);
        
        if (!$fileType) {
            return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and MP4 are allowed.'];
        }
        
        // Validate based on type
        if ($fileType === 'image') {
            $validation = isValidImage($file);
        } else {
            $validation = isValidVideo($file);
        }
        
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['error']];
        }
        
        // Generate unique filename
        $extension = getFileExtension($file['name']);
        $filename = uniqid('content_') . '_' . time() . '.' . $extension;
        $filePath = UPLOAD_PATH . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return ['success' => false, 'message' => 'Failed to save file'];
        }
        
        // Get image dimensions if image
        $width = null;
        $height = null;
        if ($fileType === 'image') {
            list($width, $height) = getimagesize($filePath);
        }
        
        // Get video duration if video
        if ($fileType === 'video') {
            $videoDuration = $this->getVideoDuration($filePath);
            if ($videoDuration > 0) {
                $duration = $videoDuration;
            }
        }
        
        // Generate thumbnail
        $thumbnailPath = null;
        if ($fileType === 'image') {
            $thumbnailPath = $this->generateThumbnail($filePath, $filename);
        }
        
        // Insert into database
        try {
            $contentId = $this->db->insert('content', [
                'user_id' => $this->userId,
                'filename' => $filename,
                'original_filename' => $file['name'],
                'file_type' => $fileType,
                'mime_type' => $mimeType,
                'file_size' => $file['size'],
                'file_path' => 'uploads/content/' . $filename,
                'thumbnail_path' => $thumbnailPath,
                'duration' => $duration,
                'width' => $width,
                'height' => $height,
                'title' => $title ?: $file['name'],
                'is_active' => 1
            ]);
            
            return [
                'success' => true,
                'message' => 'File uploaded successfully',
                'content_id' => $contentId,
                'filename' => $filename
            ];
        } catch (Exception $e) {
            // Clean up file if database insert fails
            @unlink($filePath);
            if ($thumbnailPath) {
                @unlink(THUMBNAIL_PATH . basename($thumbnailPath));
            }
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Generate thumbnail for image
     */
    private function generateThumbnail($sourcePath, $filename) {
        $thumbnailFilename = 'thumb_' . $filename;
        $thumbnailPath = THUMBNAIL_PATH . $thumbnailFilename;
        
        // Get image info
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return null;
        }
        
        $mimeType = $imageInfo['mime'];
        
        // Create image resource based on type
        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                $source = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $source = imagecreatefrompng($sourcePath);
                break;
            default:
                return null;
        }
        
        if (!$source) {
            return null;
        }
        
        // Calculate thumbnail dimensions (300px max width)
        $maxWidth = 300;
        $originalWidth = imagesx($source);
        $originalHeight = imagesy($source);
        
        if ($originalWidth > $maxWidth) {
            $thumbWidth = $maxWidth;
            $thumbHeight = floor($originalHeight * ($maxWidth / $originalWidth));
        } else {
            $thumbWidth = $originalWidth;
            $thumbHeight = $originalHeight;
        }
        
        // Create thumbnail
        $thumbnail = imagecreatetruecolor($thumbWidth, $thumbHeight);
        
        // Preserve transparency for PNG
        if ($mimeType === 'image/png') {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
        }
        
        // Resize
        imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $originalWidth, $originalHeight);
        
        // Save thumbnail
        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                imagejpeg($thumbnail, $thumbnailPath, 85);
                break;
            case 'image/png':
                imagepng($thumbnail, $thumbnailPath, 8);
                break;
        }
        
        // Free memory
        imagedestroy($source);
        imagedestroy($thumbnail);
        
        return 'uploads/thumbnails/' . $thumbnailFilename;
    }
    
    /**
     * Get video duration in seconds
     */
    private function getVideoDuration($filePath) {
        // Try using getID3 if available
        if (class_exists('getID3')) {
            try {
                $getID3 = new getID3();
                $fileInfo = $getID3->analyze($filePath);
                if (isset($fileInfo['playtime_seconds'])) {
                    return (int)round($fileInfo['playtime_seconds']);
                }
            } catch (Exception $e) {
                // Fall through to ffprobe
            }
        }
        
        // Try using ffprobe (most reliable)
        $ffprobe = shell_exec("which ffprobe 2>/dev/null");
        if ($ffprobe) {
            $cmd = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($filePath) . " 2>&1";
            $duration = shell_exec($cmd);
            if ($duration && is_numeric(trim($duration))) {
                return (int)round(floatval(trim($duration)));
            }
        }
        
        // Fallback: return 0 to use default duration
        return 0;
    }
    
    /**
     * Determine file type from MIME type
     */
    private function getFileType($mimeType) {
        if (in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
            return 'image';
        } elseif (in_array($mimeType, ALLOWED_VIDEO_TYPES)) {
            return 'video';
        }
        return null;
    }
    
    /**
     * Delete content and associated files
     */
    public function delete($contentId) {
        // Get content info
        $content = $this->db->fetchOne(
            "SELECT * FROM content WHERE id = ? AND user_id = ?",
            [$contentId, $this->userId]
        );
        
        if (!$content) {
            return ['success' => false, 'message' => 'Content not found'];
        }
        
        // Delete files
        $filePath = UPLOAD_PATH . $content['filename'];
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
        
        if ($content['thumbnail_path']) {
            $thumbPath = THUMBNAIL_PATH . basename($content['thumbnail_path']);
            if (file_exists($thumbPath)) {
                @unlink($thumbPath);
            }
        }
        
        // Delete from database
        try {
            $this->db->delete('content', 'id = :id', ['id' => $contentId]);
            return ['success' => true, 'message' => 'Content deleted successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to delete content'];
        }
    }
}
