# Google Calendar Integration Setup Guide

This guide will help you set up Google Calendar integration for the EVSU Student Portal.

## Prerequisites

1. PHP 7.4 or higher
2. Composer (for dependency management)
3. A Google Cloud Platform account
4. Access to Google Calendar API

## Step 1: Install Dependencies

Run the following command in your project root directory:

```bash
composer install
```

This will install the Google API PHP client library.

## Step 2: Set Up Google Cloud Project

1. Go to the [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable the Google Calendar API:
   - Go to "APIs & Services" > "Library"
   - Search for "Google Calendar API"
   - Click on it and press "Enable"

## Step 3: Create OAuth 2.0 Credentials

1. In Google Cloud Console, go to "APIs & Services" > "Credentials"
2. Click "Create Credentials" > "OAuth 2.0 Client IDs"
3. Choose "Web application" as the application type
4. Set the authorized redirect URIs:
   - For local development: `http://localhost/student_portal/google_calendar_callback.php`
   - For production: `https://yourdomain.com/student_portal/google_calendar_callback.php`
5. Download the JSON file

## Step 4: Configure Credentials

1. Copy the downloaded JSON file to `includes/google_credentials.json`
2. Or use the template file:
   ```bash
   cp includes/google_credentials.json.example includes/google_credentials.json
   ```
3. Edit `includes/google_credentials.json` with your actual credentials

## Step 5: Set Up Directory Permissions

Create the tokens directory and set proper permissions:

```bash
mkdir tokens
chmod 755 tokens
```

## Step 6: Update Configuration (Optional)

You can customize the integration by modifying constants in `includes/google_calendar.php`:

- `SCOPES`: Calendar permissions needed
- `APPLICATION_NAME`: Name shown in Google authorization

## Step 7: Test the Integration

1. Log in to the student portal
2. Go to "Calendar Sync" in the navigation
3. Click "Connect Google Calendar"
4. Complete the OAuth authorization
5. Try syncing your schedule

## Features

### What Gets Synced

- **Class Schedule**: All enrolled subjects with their schedules
- **Recurring Events**: Classes repeat weekly according to schedule
- **Event Details**: Subject code, name, instructor, and room information
- **Reminders**: Automatic reminders 15 and 5 minutes before class

### Calendar Management

- Creates a dedicated calendar for EVSU classes
- Clears old events before syncing new ones
- Maintains separate calendar from personal events

## Security Considerations

1. **Token Storage**: Student tokens are stored securely in the `tokens/` directory
2. **Permissions**: Only calendar read/write permissions are requested
3. **Revocation**: Students can revoke access at any time

## Troubleshooting

### Common Issues

1. **"Failed to initialize Google Calendar client"**
   - Check that `google_credentials.json` exists and has correct format
   - Verify Google Calendar API is enabled

2. **"OAuth authorization failed"**
   - Verify redirect URIs match exactly in Google Cloud Console
   - Check that the callback URL is accessible

3. **"Invalid time format"**
   - Ensure schedule data follows format: "MWF 8:00-9:00 AM" or "TTH 10:00-11:30 AM"

4. **Permissions errors**
   - Check that `tokens/` directory exists and is writable
   - Verify file permissions are set correctly

### Debug Mode

To enable debug logging, check the logs in:
- `logs/error.log` for general errors
- PHP error logs for detailed debugging

## API Limits

Google Calendar API has the following limits:
- 1,000,000 queries per day
- 100 queries per 100 seconds per user
- 1,000 queries per 100 seconds

These limits are sufficient for normal student portal usage.

## Production Deployment

For production deployment:

1. Update redirect URIs in Google Cloud Console
2. Set proper file permissions on server
3. Ensure HTTPS is enabled
4. Consider implementing rate limiting
5. Monitor API usage in Google Cloud Console

## Support

If you encounter issues:

1. Check the error logs first
2. Verify all setup steps were completed
3. Test with a single user account first
4. Contact support with specific error messages

## File Structure

```
student_portal/
├── includes/
│   ├── google_calendar.php          # Main integration class
│   ├── google_credentials.json      # OAuth credentials (not in repo)
│   └── google_credentials.json.example # Template file
├── tokens/                          # Student OAuth tokens (auto-created)
├── calendar_settings.php            # Calendar management interface
├── google_calendar_callback.php     # OAuth callback handler
├── composer.json                    # Dependencies
└── GOOGLE_CALENDAR_SETUP.md        # This file
```

## Next Steps

After successful setup, consider:

1. Adding email notifications for sync status
2. Implementing batch sync for multiple students
3. Adding integration with assignment due dates
4. Creating mobile app deep links to calendar events 