#!/bin/bash
#
# Digital Signage Platform - Raspberry Pi Installer
# One-line install: curl -sSL https://dsp.my-toolbox.info/install.sh | bash
#
# This script configures a Raspberry Pi to run as a digital signage display:
# - Installs Chromium browser in kiosk mode
# - Configures auto-login and auto-start
# - Sets up the pairing page to show on boot
# - Hides cursor and UI elements for clean display
#

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
DSP_URL="${DSP_URL:-https://dsp.my-toolbox.info}"
INSTALL_DIR="/opt/dsp-player"

echo -e "${BLUE}"
echo "╔════════════════════════════════════════════════════════════╗"
echo "║                                                            ║"
echo "║     Digital Signage Platform - Raspberry Pi Setup         ║"
echo "║                                                            ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

# Check if running on Raspberry Pi
if ! grep -q "Raspberry Pi" /proc/cpuinfo 2>/dev/null && ! grep -q "BCM" /proc/cpuinfo 2>/dev/null; then
    echo -e "${YELLOW}Warning: This doesn't appear to be a Raspberry Pi.${NC}"
    echo -e "This script is designed for Raspberry Pi OS."
    read -p "Continue anyway? (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# Check if running as root
if [ "$EUID" -eq 0 ]; then
    echo -e "${RED}Error: Please do not run this script as root or with sudo.${NC}"
    echo "The script will ask for sudo password when needed."
    exit 1
fi

echo -e "${GREEN}[1/7]${NC} Updating system packages..."
sudo apt-get update -qq

echo -e "${GREEN}[2/7]${NC} Installing required packages..."
sudo apt-get install -y -qq \
    chromium-browser \
    unclutter \
    xdotool \
    x11-xserver-utils \
    matchbox-window-manager

echo -e "${GREEN}[3/7]${NC} Creating installation directory..."
sudo mkdir -p "$INSTALL_DIR"
sudo chown $USER:$USER "$INSTALL_DIR"

# Generate unique device ID
DEVICE_ID="dsp_$(cat /proc/cpuinfo | grep Serial | cut -d ' ' -f 2 | tail -c 9)"
echo "$DEVICE_ID" > "$INSTALL_DIR/device_id"

echo -e "${GREEN}[4/7]${NC} Creating kiosk startup script..."
cat > "$INSTALL_DIR/start-kiosk.sh" << 'EOFSCRIPT'
#!/bin/bash
# DSP Kiosk Startup Script

# Get device ID from RPi serial
DEVICE_ID=$(cat /proc/cpuinfo | grep Serial | cut -d ' ' -f 2 | sed 's/^/dsp_/')

# Always start with boot.php - it will check localStorage and redirect
START_URL="https://dsp.my-toolbox.info/boot.php?device_id=${DEVICE_ID}"

# Disable screen blanking and power management
xset s off
xset -dpms
xset s noblank

# Hide cursor
unclutter -idle 0.1 -root &

# Launch Chromium in kiosk mode
chromium-browser \
  --kiosk \
  --noerrdialogs \
  --disable-infobars \
  --no-first-run \
  --check-for-update-interval=31536000 \
  --disable-session-crashed-bubble \
  --disable-features=TranslateUI \
  --password-store=basic \
  "${START_URL}"
EOFSCRIPT

chmod +x "$INSTALL_DIR/start-kiosk.sh"

echo -e "${GREEN}[5/7]${NC} Configuring auto-login..."

# Enable auto-login for current user
sudo mkdir -p /etc/systemd/system/getty@tty1.service.d/
sudo tee /etc/systemd/system/getty@tty1.service.d/autologin.conf > /dev/null << EOF
[Service]
ExecStart=
ExecStart=-/sbin/agetty --autologin $USER --noclear %I \$TERM
EOF

# Configure auto-start X on login
if ! grep -q "startx" ~/.bash_profile 2>/dev/null; then
    cat >> ~/.bash_profile << 'EOF'

# Auto-start X server on login (tty1 only)
if [ -z "$DISPLAY" ] && [ "$(tty)" = "/dev/tty1" ]; then
    startx -- -nocursor
fi
EOF
fi

echo -e "${GREEN}[6/7]${NC} Creating X startup configuration..."

# Create .xinitrc to launch kiosk with matchbox window manager
cat > ~/.xinitrc << 'EOF'
#!/bin/bash
xset s off
xset -dpms
xset s noblank

# Start matchbox window manager (for kiosk mode)
matchbox-window-manager -use_titlebar no &

# Start the kiosk
exec /opt/dsp-player/start-kiosk.sh
EOF
chmod +x ~/.xinitrc

echo -e "${GREEN}[7/7]${NC} Final configuration..."

# Disable screen saver in lightdm (if exists)
if [ -f /etc/lightdm/lightdm.conf ]; then
    sudo sed -i 's/^#xserver-command=X$/xserver-command=X -s 0 -dpms/' /etc/lightdm/lightdm.conf
fi

echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║                                                            ║${NC}"
echo -e "${GREEN}║                  Installation Complete!                    ║${NC}"
echo -e "${GREEN}║                                                            ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${BLUE}Device ID:${NC} $DEVICE_ID"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "  1. Reboot your Raspberry Pi: ${BLUE}sudo reboot${NC}"
echo "  2. The pairing page will appear automatically in fullscreen"
echo "  3. Scan the QR code with your smartphone"
echo "  4. Configure your screen in the web interface"
echo "  5. Your display will start showing content!"
echo ""
echo -e "${BLUE}Useful commands:${NC}"
echo "  • Re-pair device: ${BLUE}rm -rf ~/.config/chromium/Default/Local\\ Storage/leveldb/ && sudo reboot${NC}"
echo "  • Access terminal: ${BLUE}Press Ctrl+Alt+F2${NC}"
echo "  • Return to kiosk: ${BLUE}Press Ctrl+Alt+F1${NC}"
echo ""
read -p "Reboot now? (Y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Nn]$ ]]; then
    echo "Rebooting..."
    sudo reboot
fi
