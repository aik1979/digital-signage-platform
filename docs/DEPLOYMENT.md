# Deployment Guide - Plesk Server

This guide explains how to deploy the Digital Signage Platform to your Plesk server using Git.

## Prerequisites

- Plesk server with Git integration enabled
- MySQL database access
- PHP 8.2+ installed
- HTTPS/SSL certificate (recommended)

## Deployment Steps

### Step 1: Create Website in Plesk

1. Log in to your Plesk control panel
2. Click **"Websites & Domains"**
3. Click **"Add Domain"** or **"Add Subdomain"**
4. Enter your domain name (or use a temporary subdomain if you don't have a domain yet)
5. Choose **"Deploy using Git"** option

### Step 2: Configure Git Repository

1. In the Git deployment screen, enter:
   - **Repository URL**: `https://github.com/aik1979/digital-signage-platform.git`
   - **Repository branch**: `master`
   - **Deployment mode**: Choose **"Automatic"** for auto-updates when you push changes
   
2. Click **"OK"** to clone the repository

3. Plesk will clone the repository to your web space

### Step 3: Set Document Root

The web application files are in the `web/` subdirectory, so you need to update the document root:

1. Go to **"Hosting Settings"** for your domain
2. Change the **"Document root"** to: `/web`
3. Save the changes

### Step 4: Create MySQL Database

1. In Plesk, go to **"Databases"**
2. Click **"Add Database"**
3. Create a new database with these details:
   - **Database name**: `signage_db` (or your preferred name)
   - **Database user**: Create a new user with a strong password
   - **Privileges**: Grant all privileges
4. Note down the database credentials

### Step 5: Import Database Schema

1. In Plesk, go to **"Databases"** > **"phpMyAdmin"**
2. Select your newly created database
3. Click **"Import"** tab
4. Upload the file: `database/schema.sql` from your repository
5. Click **"Go"** to import the schema

### Step 6: Configure Application

1. Access your server via **SSH** or use Plesk **"File Manager"**
2. Navigate to your document root (where the `web/` folder is)
3. Copy the configuration template:
   ```bash
   cp web/config/config.sample.php web/config/config.php
   ```
4. Edit `web/config/config.php` with your database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'signage_db');
   define('DB_USER', 'your_db_user');
   define('DB_PASS', 'your_db_password');
   ```
5. Update the `APP_URL` to match your domain:
   ```php
   define('APP_URL', 'https://yourdomain.com');
   ```

### Step 7: Set File Permissions

Ensure the following directories are writable by the web server:

```bash
chmod 755 web/uploads/content
chmod 755 web/uploads/thumbnails
chmod 755 web/sessions
chmod 755 web/logs
```

In Plesk, you can do this via:
1. **"File Manager"**
2. Right-click each directory
3. Select **"Change Permissions"**
4. Set to **755** (or 775 if needed)

### Step 8: Configure PHP Settings

1. In Plesk, go to **"PHP Settings"** for your domain
2. Ensure these settings are configured:
   - **upload_max_filesize**: `50M` (for video uploads)
   - **post_max_size**: `50M`
   - **max_execution_time**: `300` (5 minutes)
   - **memory_limit**: `256M`

### Step 9: Enable HTTPS (Recommended)

1. In Plesk, go to **"SSL/TLS Certificates"**
2. Use **"Let's Encrypt"** for a free SSL certificate, or upload your own
3. Enable **"Permanent SEO-safe 301 redirect from HTTP to HTTPS"**

### Step 10: Test the Installation

1. Visit your domain in a web browser
2. You should see the login page
3. Default admin credentials (CHANGE IMMEDIATELY):
   - **Email**: `admin@example.com`
   - **Password**: `admin123`

## Automatic Updates via Git

If you chose automatic deployment mode:

1. Any changes pushed to the `master` branch will automatically deploy
2. Plesk will pull the latest changes within a few minutes
3. You can also manually trigger updates in Plesk:
   - Go to **"Git"** section
   - Click **"Pull Updates"**

## Troubleshooting

### White Screen / 500 Error
- Check PHP error logs in Plesk
- Verify file permissions
- Ensure config.php exists and has correct database credentials

### Database Connection Failed
- Verify database credentials in config.php
- Check that the database user has proper privileges
- Ensure MySQL is running

### Upload Directory Not Writable
- Check directory permissions (755 or 775)
- Verify the web server user has write access
- Check Plesk file permissions settings

### Git Deployment Not Working
- Verify the repository URL is correct
- Check that the branch name is `master` (not `main`)
- Ensure Plesk has network access to GitHub
- Check Plesk Git logs for error messages

## Security Checklist

Before going live, ensure:

- ✅ Changed default admin password
- ✅ Updated config.php with strong database password
- ✅ HTTPS is enabled
- ✅ APP_ENV is set to 'production' in config.php
- ✅ Removed or secured phpMyAdmin access
- ✅ Regular backups are configured in Plesk

## Support

For deployment issues, check:
1. Plesk error logs: **"Logs"** > **"Error Log"**
2. PHP error logs: `web/logs/php-errors.log`
3. Application logs: `web/logs/app.log`

## Next Steps

After successful deployment:
1. Change the default admin password
2. Create your first screen
3. Upload some content
4. Set up your Raspberry Pi (see `docs/setup-guide.md`)
