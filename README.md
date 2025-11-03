# Digital Signage Platform

A self-hosted, web-based digital signage management system for small businesses using Raspberry Pi hardware.

## Overview

This platform allows business owners to manage digital menu boards and promotional displays remotely. Built for simplicity and affordability, it eliminates recurring subscription fees while providing professional digital signage capabilities.

## Features

- **Web-Based Management**: Control all your screens from any browser
- **Content Management**: Upload and organize images and videos
- **Playlist Creation**: Build and schedule content playlists
- **Screen Monitoring**: Real-time status of all connected devices
- **Raspberry Pi Client**: Affordable hardware solution (£35-50 per screen)
- **Multi-Tenant**: Support multiple users and businesses
- **Easy Setup**: Pre-configured images and simple installation

## Technology Stack

### Web Application
- **Backend**: PHP 8.2+
- **Database**: MySQL 8.0+
- **Frontend**: HTML5, CSS3, JavaScript (jQuery)
- **Server**: Plesk hosting environment

### Raspberry Pi Client
- **Platform**: Raspberry Pi 4 / 3B+
- **OS**: Raspberry Pi OS Lite
- **Language**: Python 3.9+
- **Display**: Chromium (images) + VLC (videos)

## Project Structure

```
digital-signage-platform/
├── web/                    # Web application
│   ├── api/               # RESTful API endpoints
│   ├── assets/            # CSS, JS, images
│   ├── config/            # Configuration files
│   ├── includes/          # PHP includes and classes
│   ├── pages/             # Application pages
│   ├── uploads/           # User uploaded content
│   └── index.php          # Main entry point
├── pi-client/             # Raspberry Pi client software
│   ├── signage_client.py  # Main client application
│   ├── install.sh         # Installation script
│   └── config.json        # Client configuration
├── docs/                  # Documentation
│   ├── setup-guide.md     # Setup instructions
│   ├── api-docs.md        # API documentation
│   └── troubleshooting.md # Troubleshooting guide
└── database/              # Database schema and migrations
    └── schema.sql         # Initial database schema
```

## Installation

### Web Application Setup

1. Clone this repository to your Plesk server
2. Import the database schema from `database/schema.sql`
3. Configure database credentials in `web/config/config.php`
4. Ensure the `web/uploads/` directory is writable
5. Access the application via your domain

### Raspberry Pi Setup

1. Flash Raspberry Pi OS Lite to an SD card
2. Run the installation script:
   ```bash
   curl -sSL https://raw.githubusercontent.com/aik1979/digital-signage-platform/main/pi-client/install.sh | bash
   ```
3. Configure your device key in `/etc/signage/config.json`
4. Reboot the Pi

Detailed setup instructions are available in `docs/setup-guide.md`.

## Quick Start

1. **Register an account** on the web platform
2. **Add a screen** to generate a unique device key
3. **Upload content** (images/videos) to your library
4. **Create a playlist** and assign it to your screen
5. **Configure your Raspberry Pi** with the device key
6. **Watch your content display** automatically!

## Requirements

### Server Requirements
- PHP 8.2 or higher
- MySQL 8.0 or higher
- Apache/Nginx with mod_rewrite
- HTTPS enabled
- 1GB+ disk space

### Raspberry Pi Requirements
- Raspberry Pi 4 (2GB+ RAM recommended) or Pi 3B+
- 16GB+ microSD card
- HDMI display
- Internet connection (WiFi or Ethernet)
- Power supply (official recommended)

## Development Status

This project is currently in active development. Target launch: Q2 2025.

### Roadmap
- ✅ Phase 1: Core platform and authentication (Weeks 1-4)
- 🔄 Phase 2: API and Pi integration (Weeks 5-6)
- ⏳ Phase 3: Advanced features (Weeks 7-8)
- ⏳ Phase 4: Documentation (Weeks 8-9)
- ⏳ Phase 5: Testing and launch (Weeks 9-10)

## License

Proprietary - All rights reserved. This software is for internal use and authorized customers only.

## Support

For setup assistance or bug reports, please contact the development team.

## Credits

Developed for Rabs Chippy and small business digital signage needs.
