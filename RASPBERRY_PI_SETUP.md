# Raspberry Pi Digital Signage Setup Guide

This guide will help you set up a Raspberry Pi as a digital signage display for the Digital Signage Platform.

## Requirements

- Raspberry Pi 3, 4, or 5 (recommended: Pi 4 with 2GB+ RAM)
- MicroSD card (8GB minimum, 16GB+ recommended)
- Power supply
- HDMI display/TV
- Keyboard (for initial setup only)
- Internet connection (WiFi or Ethernet)

## Quick Start

### 1. Prepare Raspberry Pi OS

1. Download [Raspberry Pi Imager](https://www.raspberrypi.com/software/)
2. Flash **Raspberry Pi OS Lite (64-bit)** to your SD card
3. **Before ejecting**: Configure WiFi and enable SSH in the imager settings
4. Insert SD card into Raspberry Pi and boot

### 2. Initial Setup

Connect keyboard and monitor, then log in (default: `pi` / `raspberry`)

Update your system:
```bash
sudo apt update && sudo apt upgrade -y
```

### 3. Install DSP Player

Run the one-line installer:
```bash
curl -sSL https://dsp.my-toolbox.info/install.sh | bash
```

The installer will:
- ✅ Install Chromium browser and dependencies
- ✅ Configure kiosk mode (fullscreen, no UI)
- ✅ Set up auto-login and auto-start
- ✅ Hide mouse cursor
- ✅ Generate unique device ID

### 4. Reboot

When prompted, reboot your Raspberry Pi:
```bash
sudo reboot
```

### 5. Pair Your Device

After reboot, the pairing page will appear automatically:

1. **Scan the QR code** with your smartphone
2. **Log in** to your DSP account
3. **Configure your screen:**
   - Enter a name (e.g., "Lobby Display")
   - Select a playlist
   - Click "Pair Device"
4. **Done!** Your display will start showing content

## Advanced Configuration

### Change Display Resolution

Edit `/boot/config.txt`:
```bash
sudo nano /boot/config.txt
```

Add or modify:
```
hdmi_group=2
hdmi_mode=82  # 1920x1080 @ 60Hz
```

[Full resolution list](https://www.raspberrypi.com/documentation/computers/config_txt.html#hdmi-mode)

### Rotate Display

Add to `/boot/config.txt`:
```
display_rotate=1  # 90 degrees
display_rotate=2  # 180 degrees
display_rotate=3  # 270 degrees
```

### Re-pair Device

To pair with a different screen:
```bash
rm /opt/dsp-player/viewer_url
sudo reboot
```

### View Logs

Check kiosk status:
```bash
# View X server logs
cat ~/.local/share/xorg/Xorg.0.log

# Check if Chromium is running
ps aux | grep chromium
```

### Manual Start (for testing)

```bash
startx
```

### Disable Auto-Start

Remove from `~/.bash_profile`:
```bash
nano ~/.bash_profile
# Delete the startx section
```

## Troubleshooting

### Black Screen After Boot

1. Check HDMI cable connection
2. Try different HDMI port on TV
3. Check `/boot/config.txt` for correct settings
4. View logs: `cat ~/.local/share/xorg/Xorg.0.log`

### No Network Connection

1. Check WiFi credentials in Raspberry Pi Imager
2. Or manually configure: `sudo raspi-config` → Network Options
3. Test: `ping google.com`

### Pairing Page Not Loading

1. Check internet connection: `ping dsp.my-toolbox.info`
2. Check device ID: `cat /opt/dsp-player/device_id`
3. Manually open: `chromium-browser https://dsp.my-toolbox.info/pair.php`

### Screen Goes Blank

Disable screen saver:
```bash
sudo nano /etc/lightdm/lightdm.conf
# Add: xserver-command=X -s 0 -dpms
```

### Content Not Updating

1. Check viewer URL: `cat /opt/dsp-player/viewer_url`
2. Open browser console (Ctrl+Shift+I) for errors
3. Verify playlist has content in web dashboard

## Performance Tips

### For Raspberry Pi 3

- Use 720p resolution instead of 1080p
- Limit playlist to images only (avoid videos)
- Increase GPU memory:
  ```bash
  sudo raspi-config
  # Performance Options → GPU Memory → 256
  ```

### For Raspberry Pi 4/5

- Can handle 4K displays
- Videos play smoothly
- Recommended GPU memory: 256MB+

### Optimize Boot Time

Disable unnecessary services:
```bash
sudo systemctl disable bluetooth
sudo systemctl disable hciuart
```

## Hardware Recommendations

### Best Performance
- **Raspberry Pi 5** (8GB RAM)
- Class 10 SD card or SSD boot
- Active cooling (fan)

### Budget Option
- **Raspberry Pi 4** (2GB RAM)
- Class 10 SD card
- Passive heatsink

### Minimum
- **Raspberry Pi 3 B+**
- 720p display
- Image-only content

## Network Setup

### Static IP (Optional)

Edit `/etc/dhcpcd.conf`:
```bash
sudo nano /etc/dhcpcd.conf
```

Add:
```
interface eth0
static ip_address=192.168.1.100/24
static routers=192.168.1.1
static domain_name_servers=8.8.8.8
```

### Remote Access via SSH

Enable SSH:
```bash
sudo systemctl enable ssh
sudo systemctl start ssh
```

Connect from another computer:
```bash
ssh pi@<raspberry-pi-ip>
```

## Security Notes

- Change default password: `passwd`
- Keep system updated: `sudo apt update && sudo apt upgrade`
- Use firewall if exposed to internet
- Disable SSH if not needed: `sudo systemctl disable ssh`

## Support

- **Documentation**: https://dsp.my-toolbox.info/docs
- **Issues**: https://github.com/aik1979/digital-signage-platform/issues
- **Email**: support@dsp.my-toolbox.info

## Uninstall

To remove DSP player:
```bash
sudo rm -rf /opt/dsp-player
rm ~/.xinitrc
rm ~/.bash_profile
sudo systemctl disable getty@tty1
sudo reboot
```
