<?php
/**
 * Raspberry Pi Setup Guide
 * Public page with comprehensive setup instructions
 */

// Start session
session_start();

// Load configuration
require_once __DIR__ . '/config/config.php';

// Load core includes
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/functions.php';

// Initialize database connection
$db = Database::getInstance();

// Check if user is logged in
$auth = new Auth($db);
$isLoggedIn = $auth->isLoggedIn();

// Load user data if logged in
$user = [];
if ($isLoggedIn) {
    $user = $auth->getUser();
}

// Set page variable for header navigation highlighting
$page = 'raspberry-pi-setup';

// Include header
include __DIR__ . '/includes/header.php';
?>

<style>
    .step-number {
        background: linear-gradient(135deg, #3498DB 0%, #2980B9 100%);
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 18px;
        flex-shrink: 0;
    }
    
    .code-block {
        background: #1a1a1a;
        border: 1px solid #333;
        border-radius: 8px;
        padding: 16px;
        font-family: 'Courier New', monospace;
        color: #5CB85C;
        position: relative;
        overflow-x: auto;
    }
    
    .copy-btn {
        position: absolute;
        top: 8px;
        right: 8px;
        background: #3498DB;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        transition: background 0.3s;
    }
    
    .copy-btn:hover {
        background: #2980B9;
    }
    
    .warning-box {
        background: #FFF3CD;
        border-left: 4px solid #FFC107;
        padding: 16px;
        border-radius: 4px;
        margin: 16px 0;
        color: #000;
    }
    
    .info-box {
        background: #D1ECF1;
        border-left: 4px solid #17A2B8;
        padding: 16px;
        border-radius: 4px;
        margin: 16px 0;
        color: #000;
    }
    
    .success-box {
        background: #D4EDDA;
        border-left: 4px solid #5CB85C;
        padding: 16px;
        border-radius: 4px;
        margin: 16px 0;
        color: #000;
    }
</style>

<div class="space-y-8">
    <!-- Header -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-8">
        <h1 class="text-4xl font-bold text-white mb-4">
            <i class="fas fa-raspberry-pi text-red-500 mr-3"></i>
            Raspberry Pi Setup Guide
        </h1>
        <p class="text-gray-300 text-lg">
            Follow this step-by-step guide to set up your Raspberry Pi as a digital signage display. 
            This guide is designed for complete beginners - no technical experience required!
        </p>
    </div>

    <!-- What You Need -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-8">
        <h2 class="text-2xl font-bold text-white mb-4">
            <i class="fas fa-shopping-cart text-blue-500 mr-2"></i>
            What You Need
        </h2>
        <ul class="space-y-3 text-gray-300">
            <li class="flex items-start">
                <i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i>
                <span><strong>Raspberry Pi</strong> (Model 3, 4, or 5 recommended)</span>
            </li>
            <li class="flex items-start">
                <i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i>
                <span><strong>MicroSD Card</strong> (16GB or larger, Class 10 recommended)</span>
            </li>
            <li class="flex items-start">
                <i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i>
                <span><strong>Power Supply</strong> (Official Raspberry Pi power supply recommended)</span>
            </li>
            <li class="flex items-start">
                <i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i>
                <span><strong>Display</strong> (TV or monitor with HDMI input)</span>
            </li>
            <li class="flex items-start">
                <i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i>
                <span><strong>HDMI Cable</strong></span>
            </li>
            <li class="flex items-start">
                <i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i>
                <span><strong>Keyboard</strong> (USB, only needed for initial setup)</span>
            </li>
            <li class="flex items-start">
                <i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i>
                <span><strong>Internet Connection</strong> (WiFi or Ethernet cable)</span>
            </li>
            <li class="flex items-start">
                <i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i>
                <span><strong>Smartphone</strong> (for scanning QR code during pairing)</span>
            </li>
        </ul>
    </div>

    <!-- Step 1: Install Raspberry Pi OS -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-8">
        <div class="flex items-start mb-4">
            <div class="step-number mr-4">1</div>
            <h2 class="text-2xl font-bold text-white">Install Raspberry Pi OS</h2>
        </div>
        
        <div class="ml-14 text-gray-300">
            <p class="mb-4">
                First, we need to install the operating system on your Raspberry Pi.
            </p>
            
            <ol class="list-decimal list-inside space-y-4">
                <li>
                    <strong class="text-white">Download Raspberry Pi Imager</strong>
                    <p class="ml-6 mt-2">Visit <a href="https://www.raspberrypi.com/software/" target="_blank" class="text-blue-400 hover:underline">raspberrypi.com/software</a> and download the Raspberry Pi Imager for your computer (Windows, Mac, or Linux).</p>
                </li>
                <li>
                    <strong class="text-white">Install and Open Raspberry Pi Imager</strong>
                    <p class="ml-6 mt-2">Install the software and launch it.</p>
                </li>
                <li>
                    <strong class="text-white">Choose Operating System</strong>
                    <p class="ml-6 mt-2">Click "Choose OS" → Select <strong>"Raspberry Pi OS (64-bit)"</strong> (the first option in the list)</p>
                </li>
                <li>
                    <strong class="text-white">Choose Storage</strong>
                    <p class="ml-6 mt-2">Click "Choose Storage" → Select your microSD card</p>
                    <div class="warning-box ml-6 mt-2">
                        <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                        <strong>Warning:</strong> This will erase everything on the SD card!
                    </div>
                </li>
                <li>
                    <strong class="text-white">Configure Settings (Important!)</strong>
                    <p class="ml-6 mt-2">Click the gear icon ⚙️ (or "Edit Settings") and configure:</p>
                    <ul class="ml-12 mt-2 space-y-2">
                        <li>✓ Set hostname: <code class="bg-gray-700 px-2 py-1 rounded">raspberrypi</code> (or any name you prefer)</li>
                        <li>✓ Enable SSH (check "Enable SSH")</li>
                        <li>✓ Set username and password (remember these!)</li>
                        <li>✓ Configure WiFi (enter your WiFi name and password)</li>
                        <li>✓ Set locale settings (timezone and keyboard layout)</li>
                    </ul>
                </li>
                <li>
                    <strong class="text-white">Write to SD Card</strong>
                    <p class="ml-6 mt-2">Click "Write" and wait for the process to complete (this may take 5-10 minutes)</p>
                </li>
                <li>
                    <strong class="text-white">Insert SD Card into Raspberry Pi</strong>
                    <p class="ml-6 mt-2">Once complete, safely eject the SD card and insert it into your Raspberry Pi</p>
                </li>
            </ol>
        </div>
    </div>

    <!-- Step 2: First Boot -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-8">
        <div class="flex items-start mb-4">
            <div class="step-number mr-4">2</div>
            <h2 class="text-2xl font-bold text-white">First Boot and Initial Setup</h2>
        </div>
        
        <div class="ml-14 text-gray-300">
            <ol class="list-decimal list-inside space-y-4">
                <li>
                    <strong class="text-white">Connect Everything</strong>
                    <ul class="ml-6 mt-2 space-y-2">
                        <li>• Connect HDMI cable to your display</li>
                        <li>• Connect USB keyboard</li>
                        <li>• Connect Ethernet cable (if not using WiFi)</li>
                        <li>• Connect power supply (this will turn on the Pi)</li>
                    </ul>
                </li>
                <li>
                    <strong class="text-white">Wait for Boot</strong>
                    <p class="ml-6 mt-2">The Raspberry Pi will boot up (you'll see text scrolling on the screen). This takes about 1-2 minutes on first boot.</p>
                </li>
                <li>
                    <strong class="text-white">Login</strong>
                    <p class="ml-6 mt-2">When you see the login prompt, enter the username and password you set in Step 1.</p>
                </li>
            </ol>
            
            <div class="info-box mt-4">
                <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                <strong>Tip:</strong> If you see a desktop environment, that's fine! Open the Terminal application (black icon with >_ symbol).
            </div>
        </div>
    </div>

    <!-- Step 3: Run the Installer -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-8">
        <div class="flex items-start mb-4">
            <div class="step-number mr-4">3</div>
            <h2 class="text-2xl font-bold text-white">Install Digital Signage Software</h2>
        </div>
        
        <div class="ml-14 text-gray-300">
            <p class="mb-4">
                Now we'll install the digital signage software with a single command. This is the easiest part!
            </p>
            
            <ol class="list-decimal list-inside space-y-4">
                <li>
                    <strong class="text-white">Copy the Installation Command</strong>
                    <p class="ml-6 mt-2">Click the "Copy" button below to copy the installation command:</p>
                    <div class="code-block ml-6 mt-2">
                        <button class="copy-btn" onclick="copyToClipboard('install-cmd')">
                            <i class="fas fa-copy mr-1"></i> Copy
                        </button>
                        <code id="install-cmd">curl -sSL https://dsp.my-toolbox.info/install.sh | bash</code>
                    </div>
                </li>
                <li>
                    <strong class="text-white">Paste and Run</strong>
                    <p class="ml-6 mt-2">In the terminal on your Raspberry Pi:</p>
                    <ul class="ml-12 mt-2 space-y-2">
                        <li>• Right-click to paste (or press Ctrl+Shift+V)</li>
                        <li>• Press Enter to run the command</li>
                    </ul>
                </li>
                <li>
                    <strong class="text-white">Wait for Installation</strong>
                    <p class="ml-6 mt-2">The installer will:</p>
                    <ul class="ml-12 mt-2 space-y-2">
                        <li>✓ Update your system</li>
                        <li>✓ Install required software (Chromium browser, X server, etc.)</li>
                        <li>✓ Configure kiosk mode</li>
                        <li>✓ Set up auto-boot</li>
                    </ul>
                    <div class="info-box ml-6 mt-2">
                        <i class="fas fa-clock text-blue-600 mr-2"></i>
                        This process takes about 5-10 minutes. You'll see progress messages on the screen.
                    </div>
                </li>
                <li>
                    <strong class="text-white">Reboot When Prompted</strong>
                    <p class="ml-6 mt-2">When the installation completes, you'll see a message asking you to reboot. Type:</p>
                    <div class="code-block ml-6 mt-2">
                        <button class="copy-btn" onclick="copyToClipboard('reboot-cmd')">
                            <i class="fas fa-copy mr-1"></i> Copy
                        </button>
                        <code id="reboot-cmd">sudo reboot</code>
                    </div>
                    <p class="ml-6 mt-2">Press Enter and wait for the Pi to restart.</p>
                </li>
            </ol>
        </div>
    </div>

    <!-- Step 4: Pair Your Device -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-8">
        <div class="flex items-start mb-4">
            <div class="step-number mr-4">4</div>
            <h2 class="text-2xl font-bold text-white">Pair Your Display</h2>
        </div>
        
        <div class="ml-14 text-gray-300">
            <p class="mb-4">
                After rebooting, your Raspberry Pi will automatically start in kiosk mode and show a QR code for pairing.
            </p>
            
            <ol class="list-decimal list-inside space-y-4">
                <li>
                    <strong class="text-white">Wait for QR Code</strong>
                    <p class="ml-6 mt-2">After the Pi reboots, it will automatically open a fullscreen browser showing a large QR code and pairing code.</p>
                </li>
                <li>
                    <strong class="text-white">Scan QR Code with Your Phone</strong>
                    <p class="ml-6 mt-2">Use your smartphone camera to scan the QR code on the screen.</p>
                    <div class="info-box ml-6 mt-2">
                        <i class="fas fa-mobile-alt text-blue-600 mr-2"></i>
                        <strong>Tip:</strong> On iPhone, just open the Camera app. On Android, use Google Lens or your camera app.
                    </div>
                </li>
                <li>
                    <strong class="text-white">Configure Your Screen</strong>
                    <p class="ml-6 mt-2">Your phone will open a configuration page. Fill in:</p>
                    <ul class="ml-12 mt-2 space-y-2">
                        <li>• Screen name (e.g., "Lobby Display", "Conference Room TV")</li>
                        <li>• Location (optional)</li>
                        <li>• Select which playlist to display</li>
                    </ul>
                </li>
                <li>
                    <strong class="text-white">Save Configuration</strong>
                    <p class="ml-6 mt-2">Click "Pair Device" on your phone.</p>
                </li>
                <li>
                    <strong class="text-white">Watch It Start!</strong>
                    <p class="ml-6 mt-2">Within a few seconds, your Raspberry Pi screen will:</p>
                    <ul class="ml-12 mt-2 space-y-2">
                        <li>✓ Show a 10-second splash screen</li>
                        <li>✓ Automatically load your content</li>
                        <li>✓ Start displaying your playlist in fullscreen</li>
                    </ul>
                </li>
            </ol>
            
            <div class="success-box mt-4">
                <i class="fas fa-check-circle text-green-600 mr-2"></i>
                <strong>Success!</strong> Your digital signage display is now running! You can disconnect the keyboard - it's no longer needed.
            </div>
        </div>
    </div>

    <!-- Troubleshooting -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-8">
        <h2 class="text-2xl font-bold text-white mb-4">
            <i class="fas fa-wrench text-orange-500 mr-2"></i>
            Troubleshooting
        </h2>
        
        <div class="space-y-4 text-gray-300">
            <div class="border-l-4 border-blue-500 pl-4">
                <h3 class="font-bold text-white mb-2">Screen shows "half screen" or content not fullscreen</h3>
                <p>This should be fixed automatically by the installer. If you still see this, the matchbox window manager may not be installed. Connect a keyboard and run:</p>
                <div class="code-block mt-2">
                    <code>sudo apt-get install -y matchbox-window-manager</code>
                </div>
                <p class="mt-2">Then reboot: <code class="bg-gray-700 px-2 py-1 rounded">sudo reboot</code></p>
            </div>
            
            <div class="border-l-4 border-blue-500 pl-4">
                <h3 class="font-bold text-white mb-2">QR code doesn't appear after reboot</h3>
                <p>Check your internet connection. The Pi needs internet to load the pairing page. Try:</p>
                <ul class="ml-6 mt-2 space-y-1">
                    <li>• Connect an Ethernet cable</li>
                    <li>• Check your WiFi settings were saved correctly</li>
                    <li>• Run <code class="bg-gray-700 px-2 py-1 rounded">ping google.com</code> to test connectivity</li>
                </ul>
            </div>
            
            <div class="border-l-4 border-blue-500 pl-4">
                <h3 class="font-bold text-white mb-2">Screen is blank or black</h3>
                <p>Wait 2-3 minutes for the first boot. If still blank:</p>
                <ul class="ml-6 mt-2 space-y-1">
                    <li>• Check HDMI cable is connected properly</li>
                    <li>• Try a different HDMI port on your TV/monitor</li>
                    <li>• Press Ctrl+Alt+F2 to switch to terminal view</li>
                </ul>
            </div>
            
            <div class="border-l-4 border-blue-500 pl-4">
                <h3 class="font-bold text-white mb-2">Need to re-pair the device</h3>
                <p>Connect a keyboard and press Ctrl+Alt+F2, then login and run:</p>
                <div class="code-block mt-2">
                    <code>rm -rf ~/.config/chromium/Default/Local\ Storage/leveldb/</code>
                </div>
                <p class="mt-2">Then reboot to see the QR code again.</p>
            </div>
            
            <div class="border-l-4 border-blue-500 pl-4">
                <h3 class="font-bold text-white mb-2">Content not updating</h3>
                <p>Content updates automatically every time the playlist loops. If you need immediate update, press Ctrl+Alt+F2, login, and run:</p>
                <div class="code-block mt-2">
                    <code>sudo systemctl restart dsp-kiosk</code>
                </div>
            </div>
        </div>
    </div>

    <!-- Tips and Best Practices -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-8">
        <h2 class="text-2xl font-bold text-white mb-4">
            <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
            Tips and Best Practices
        </h2>
        
        <ul class="space-y-3 text-gray-300">
            <li class="flex items-start">
                <i class="fas fa-check text-green-500 mr-3 mt-1"></i>
                <span><strong class="text-white">Power Supply:</strong> Always use the official Raspberry Pi power supply. Cheap power supplies can cause random crashes.</span>
            </li>
            <li class="flex items-start">
                <i class="fas fa-check text-green-500 mr-3 mt-1"></i>
                <span><strong class="text-white">SD Card Quality:</strong> Use a good quality, Class 10 SD card for best performance and reliability.</span>
            </li>
            <li class="flex items-start">
                <i class="fas fa-check text-green-500 mr-3 mt-1"></i>
                <span><strong class="text-white">Cooling:</strong> Consider adding a heatsink or fan if your Pi will run 24/7.</span>
            </li>
            <li class="flex items-start">
                <i class="fas fa-check text-green-500 mr-3 mt-1"></i>
                <span><strong class="text-white">Updates:</strong> The system will auto-update content, but you can manually update the OS monthly with: <code class="bg-gray-700 px-2 py-1 rounded">sudo apt update && sudo apt upgrade -y</code></span>
            </li>
            <li class="flex items-start">
                <i class="fas fa-check text-green-500 mr-3 mt-1"></i>
                <span><strong class="text-white">Backup:</strong> Once configured, consider making a backup image of your SD card using Raspberry Pi Imager's "Read" function.</span>
            </li>
            <li class="flex items-start">
                <i class="fas fa-check text-green-500 mr-3 mt-1"></i>
                <span><strong class="text-white">Multiple Displays:</strong> You can set up as many Raspberry Pi displays as you need - each one gets its own unique QR code for pairing.</span>
            </li>
        </ul>
    </div>

    <!-- Back to Dashboard/Home -->
    <div class="text-center">
        <?php if ($isLoggedIn): ?>
            <a href="?page=dashboard" class="inline-block bg-gradient-to-r from-dsp-blue to-blue-600 text-white font-bold py-3 px-8 rounded-lg hover:from-blue-600 hover:to-blue-700 transition shadow-lg">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Dashboard
            </a>
        <?php else: ?>
            <a href="/" class="inline-block bg-gradient-to-r from-dsp-blue to-blue-600 text-white font-bold py-3 px-8 rounded-lg hover:from-blue-600 hover:to-blue-700 transition shadow-lg">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Home
            </a>
        <?php endif; ?>
    </div>
</div>

<script>
    function copyToClipboard(elementId) {
        const text = document.getElementById(elementId).textContent;
        navigator.clipboard.writeText(text).then(() => {
            // Show feedback
            const btn = event.target.closest('button');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check mr-1"></i> Copied!';
            btn.style.background = '#5CB85C';
            
            setTimeout(() => {
                btn.innerHTML = originalHTML;
                btn.style.background = '#3498DB';
            }, 2000);
        });
    }
</script>

<?php
// Include footer
include __DIR__ . '/includes/footer.php';
?>
