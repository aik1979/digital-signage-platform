# Digital Signage Platform - Player API

## Overview

The Player API provides RESTful endpoints for Raspberry Pi players to communicate with the Digital Signage Platform. Players can fetch playlist data, download content, report status, and receive configuration updates.

## Base URL

```
https://dsp.my-toolbox.info/api/player/
```

## Authentication

All endpoints require authentication using a **device key**. The device key is generated when a screen is created and must be passed as a query parameter or in the request body.

## Endpoints

### 1. Get Playlist

Fetch the assigned playlist for a screen.

**Endpoint:** `GET /playlist.php`

**Parameters:**
- `device_key` (required): The device key for authentication

**Example Request:**
```bash
curl -X GET "https://dsp.my-toolbox.info/api/player/playlist.php?device_key=abc123def456"
```

**Example Response:**
```json
{
  "success": true,
  "timestamp": "2025-11-04 12:34:56",
  "screen": {
    "id": 1,
    "name": "Lobby Display",
    "device_key": "abc123def456",
    "last_seen": "2025-11-04 12:34:56"
  },
  "playlist": {
    "id": 1,
    "name": "Main Playlist",
    "description": "Lobby content",
    "item_count": 3,
    "items": [
      {
        "id": 1,
        "content_id": 10,
        "name": "Welcome Image",
        "type": "image",
        "url": "https://dsp.my-toolbox.info/uploads/content/image1.jpg",
        "thumbnail_url": "https://dsp.my-toolbox.info/uploads/thumbnails/image1_thumb.jpg",
        "duration": 10,
        "order": 1,
        "file_size": 524288
      },
      {
        "id": 2,
        "content_id": 11,
        "name": "Promo Video",
        "type": "video",
        "url": "https://dsp.my-toolbox.info/uploads/content/video1.mp4",
        "thumbnail_url": null,
        "duration": 30,
        "order": 2,
        "file_size": 10485760
      }
    ]
  },
  "config": {
    "refresh_interval": 300,
    "transition_duration": 1000
  }
}
```

### 2. Send Heartbeat

Report player status and system metrics to the server.

**Endpoint:** `POST /heartbeat.php`

**Content-Type:** `application/json`

**Request Body:**
```json
{
  "device_key": "abc123def456",
  "status": "playing",
  "current_item_id": 1,
  "uptime": 3600,
  "ip_address": "192.168.1.100",
  "player_version": "1.0.0",
  "system_info": {
    "cpu_temp": 45.2,
    "memory_usage": 512,
    "disk_usage": 2048
  }
}
```

**Example Request:**
```bash
curl -X POST "https://dsp.my-toolbox.info/api/player/heartbeat.php" \
  -H "Content-Type: application/json" \
  -d '{
    "device_key": "abc123def456",
    "status": "playing",
    "current_item_id": 1,
    "uptime": 3600,
    "ip_address": "192.168.1.100",
    "player_version": "1.0.0",
    "system_info": {
      "cpu_temp": 45.2,
      "memory_usage": 512,
      "disk_usage": 2048
    }
  }'
```

**Example Response:**
```json
{
  "success": true,
  "message": "Heartbeat received",
  "timestamp": "2025-11-04 12:34:56",
  "screen_id": 1,
  "commands": []
}
```

**Status Values:**
- `playing`: Currently playing content
- `paused`: Playback paused
- `idle`: Player idle (no content)
- `error`: Player encountered an error
- `offline`: Player offline

### 3. Download Content

Download a specific content file.

**Endpoint:** `GET /content.php`

**Parameters:**
- `device_key` (required): The device key for authentication
- `content_id` (required): The ID of the content to download

**Example Request:**
```bash
curl -X GET "https://dsp.my-toolbox.info/api/player/content.php?device_key=abc123def456&content_id=10" \
  -o image1.jpg
```

**Response:**
- Binary file data (image or video)
- Appropriate Content-Type header
- Content-Disposition header with filename

### 4. Get Configuration

Fetch player configuration settings.

**Endpoint:** `GET /config.php`

**Parameters:**
- `device_key` (required): The device key for authentication

**Example Request:**
```bash
curl -X GET "https://dsp.my-toolbox.info/api/player/config.php?device_key=abc123def456"
```

**Example Response:**
```json
{
  "success": true,
  "timestamp": "2025-11-04 12:34:56",
  "screen": {
    "id": 1,
    "name": "Lobby Display"
  },
  "config": {
    "refresh_interval": 300,
    "heartbeat_interval": 60,
    "cache_size_mb": 1024,
    "log_level": "info",
    "retry_attempts": 3,
    "retry_delay": 5,
    "display_settings": {
      "rotation": 0,
      "brightness": 100,
      "transition_effect": "fade",
      "transition_duration": 1000
    },
    "network_settings": {
      "timeout": 30,
      "max_retries": 3
    },
    "content_settings": {
      "preload_next": true,
      "cache_enabled": true,
      "video_autoplay": true,
      "video_loop": false
    }
  }
}
```

## Error Responses

All endpoints return consistent error responses:

```json
{
  "success": false,
  "error": "Error message here",
  "error_code": "ERROR_CODE"
}
```

**Common Error Codes:**
- `INVALID_DEVICE_KEY`: Device key not found or invalid (401)
- `NO_PLAYLIST_ASSIGNED`: Screen has no playlist assigned (404)
- `CONTENT_NOT_FOUND`: Requested content does not exist (404)
- `PERMISSION_DENIED`: Screen does not have access to content (403)
- `RATE_LIMIT_EXCEEDED`: Too many requests from this device (429)
- `METHOD_NOT_ALLOWED`: Invalid HTTP method (405)
- `INVALID_JSON`: Invalid JSON in request body (400)
- `DATABASE_ERROR`: Server-side database error (500)

## Rate Limits

To prevent abuse, the following rate limits are enforced per device key:

- **Playlist API**: 60 requests per minute
- **Config API**: 60 requests per minute
- **Heartbeat API**: 120 requests per minute
- **Content API**: 300 requests per minute

Exceeding these limits will result in a `429 Too Many Requests` error.

## Best Practices

1. **Cache playlist data**: Don't fetch the playlist on every content rotation
2. **Use refresh_interval**: Check for updates at the interval specified in the response
3. **Send regular heartbeats**: Report status every 60 seconds for monitoring
4. **Handle errors gracefully**: Implement retry logic with exponential backoff
5. **Cache content locally**: Download and cache media files to reduce bandwidth
6. **Validate responses**: Always check the `success` field before processing data
7. **Use HTTPS**: Always use HTTPS in production for security

## Example Player Implementation

See the Python player implementation in `/player/dsp-player.py` for a complete example of how to use these APIs.

## Logging

All API requests are logged to `/logs/player-api-YYYY-MM-DD.log` for debugging and analytics.

## Support

For issues or questions about the Player API, please contact the development team or file an issue in the GitHub repository.
