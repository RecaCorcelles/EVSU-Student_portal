# Fix Google OAuth Verification Error

## Problem
```
Error 403: access_denied
Access blocked: studentportal has not completed the Google verification process
```

## New Problem: Insufficient Scopes
```
Error 403: Request had insufficient authentication scopes
Reason: ACCESS_TOKEN_SCOPE_INSUFFICIENT
Method: calendar.v3.Calendars.Insert
```

## Solution Steps

### 1. Go to Google Cloud Console
- Visit: https://console.cloud.google.com/
- Select project: `studentportal-462114`

### 2. Configure OAuth Consent Screen
- Navigate: APIs & Services → OAuth consent screen
- Click "EDIT APP"

### 3. Add Test Users
- Scroll to "Test users" section
- Click "+ ADD USERS"
- Add email addresses that need access:
  ```
  your-email@gmail.com
  student-email@evsu.edu.ph
  admin-email@evsu.edu.ph
  ```

### 4. Save Changes
- Click "SAVE AND CONTINUE"

### 5. Fix Scope Issues (NEW)
If you get "insufficient authentication scopes" error:

1. **Clear existing tokens** - Delete all files in `tokens/` directory
2. **Re-authorize** - Users need to go through OAuth flow again
3. **Updated scopes** - The system now requests full calendar access

The OAuth scopes have been updated from:
- `CALENDAR_READONLY` + `CALENDAR_EVENTS` 
To:
- `CALENDAR` (full access) + `CALENDAR_EVENTS`

### 6. Test Again
- Go back to your student portal
- Try the Google Calendar sync again
- Should work for added test users

## Alternative: Internal App
If this is for EVSU only:
1. User Type: Change to "Internal"
2. Domain: Add `evsu.edu.ph`
3. No verification needed

## For Production Use
1. Complete all OAuth consent screen fields
2. Add privacy policy URL
3. Add terms of service URL
4. Submit for verification
5. Wait 1-6 weeks for approval

## Quick Test
After adding test users, the integration should work immediately for those email addresses. 