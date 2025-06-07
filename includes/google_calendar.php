<?php
/**
 * Google Calendar Integration for EVSU Student Portal
 * This class handles all Google Calendar API operations
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once 'config.php';

class GoogleCalendarIntegration {
    private $client;
    private $service;
    private $studentId;
    
    // Google Calendar API Configuration
    private const SCOPES = [
        Google_Service_Calendar::CALENDAR,
        Google_Service_Calendar::CALENDAR_EVENTS
    ];
    private const APPLICATION_NAME = 'EVSU Student Portal';
    
    public function __construct($studentId = null) {
        $this->studentId = $studentId;
        $this->initializeClient();
    }
    
    /**
     * Initialize Google Client
     */
    private function initializeClient() {
        try {
            $this->client = new Google_Client();
            $this->client->setApplicationName(self::APPLICATION_NAME);
            $this->client->setScopes(self::SCOPES);
            $this->client->setAuthConfig(__DIR__ . '/google_credentials.json');
            $this->client->setAccessType('offline');
            $this->client->setPrompt('select_account consent');
            
            // Set redirect URI for OAuth
            $this->client->setRedirectUri($this->getRedirectUri());
            
            $this->service = new Google_Service_Calendar($this->client);
        } catch (Exception $e) {
            error_log("Google Calendar Client Error: " . $e->getMessage());
            throw new Exception("Failed to initialize Google Calendar client");
        }
    }
    
    /**
     * Get OAuth authorization URL
     */
    public function getAuthUrl() {
        return $this->client->createAuthUrl();
    }
    
    /**
     * Handle OAuth callback and store tokens
     */
    public function handleCallback($authCode) {
        try {
            $accessToken = $this->client->fetchAccessTokenWithAuthCode($authCode);
            
            if (array_key_exists('error', $accessToken)) {
                throw new Exception('Error fetching access token: ' . $accessToken['error']);
            }
            
            // Store token for this student
            $this->storeAccessToken($accessToken);
            
            return true;
        } catch (Exception $e) {
            error_log("OAuth Callback Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if student has authorized Google Calendar
     */
    public function isAuthorized() {
        $token = $this->getStoredAccessToken();
        if (!$token) return false;
        
        $this->client->setAccessToken($token);
        
        // Check if token is expired and try to refresh
        if ($this->client->isAccessTokenExpired()) {
            if ($this->client->getRefreshToken()) {
                $newToken = $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                $this->storeAccessToken($newToken);
                return !$this->client->isAccessTokenExpired();
            }
            return false;
        }
        
        return true;
    }
    
    /**
     * Sync class schedule to Google Calendar
     */
    public function syncScheduleToCalendar() {
        if (!$this->isAuthorized()) {
            throw new Exception("User not authorized for Google Calendar");
        }
        
        try {
            // Get student schedule data
            $scheduleData = $this->getStudentSchedule();
            
            if (empty($scheduleData)) {
                return ['success' => false, 'message' => 'No schedule data found'];
            }
            
            // Create or get calendar for classes
            $calendarId = $this->getOrCreateClassCalendar();
            
            // Clear existing events for this semester
            $this->clearExistingEvents($calendarId);
            
            $eventsCreated = 0;
            $errors = [];
            
            foreach ($scheduleData as $schedule) {
                try {
                    $this->createRecurringClassEvent($calendarId, $schedule);
                    $eventsCreated++;
                } catch (Exception $e) {
                    $errors[] = "Failed to create event for {$schedule['code']}: " . $e->getMessage();
                }
            }
            
            return [
                'success' => true,
                'events_created' => $eventsCreated,
                'errors' => $errors,
                'calendar_id' => $calendarId
            ];
            
        } catch (Exception $e) {
            error_log("Schedule Sync Error: " . $e->getMessage());
            throw new Exception("Failed to sync schedule: " . $e->getMessage());
        }
    }
    
    /**
     * Create a recurring class event
     */
    private function createRecurringClassEvent($calendarId, $schedule) {
        $event = new Google_Service_Calendar_Event([
            'summary' => $schedule['code'] . ' - ' . $schedule['desc'],
            'description' => "Course: {$schedule['code']}\nInstructor: {$schedule['instructor']}\nRoom: {$schedule['room']}",
            'location' => $schedule['room'],
            'start' => [
                'dateTime' => $this->parseScheduleDateTime($schedule['time'], true),
                'timeZone' => 'Asia/Manila',
            ],
            'end' => [
                'dateTime' => $this->parseScheduleDateTime($schedule['time'], false),
                'timeZone' => 'Asia/Manila',
            ],
            'recurrence' => [
                $this->generateRecurrenceRule($schedule['time'])
            ],
            'reminders' => [
                'useDefault' => false,
                'overrides' => [
                    ['method' => 'popup', 'minutes' => 15],
                    ['method' => 'popup', 'minutes' => 5],
                ],
            ],
            'source' => [
                'title' => 'EVSU Student Portal',
                'url' => $this->getPortalUrl()
            ]
        ]);
        
        return $this->service->events->insert($calendarId, $event);
    }
    
    /**
     * Get or create a dedicated calendar for classes
     */
    private function getOrCreateClassCalendar() {
        $calendarSummary = 'EVSU Class Schedule - ' . getCurrentAcademicYear();
        
        // Check if calendar already exists
        $calendars = $this->service->calendarList->listCalendarList();
        
        foreach ($calendars->getItems() as $calendar) {
            if ($calendar->getSummary() === $calendarSummary) {
                return $calendar->getId();
            }
        }
        
        // Create new calendar
        $calendar = new Google_Service_Calendar_Calendar([
            'summary' => $calendarSummary,
            'description' => 'Class schedule automatically synced from EVSU Student Portal',
            'timeZone' => 'Asia/Manila'
        ]);
        
        $createdCalendar = $this->service->calendars->insert($calendar);
        return $createdCalendar->getId();
    }
    
    /**
     * Parse schedule time string and return DateTime
     */
    private function parseScheduleDateTime($timeString, $isStart = true) {
        // Parse different time formats
        $timeString = trim($timeString);
        
        // Handle different time formats:
        // "Mon & Wed 10-11AM", "Tue & Thu 9-10:30AM", "Fri 8-11AM", "Fri 9-12NN", "Mon 9:00-10:30AM"
        
        $patterns = [
            // Pattern 1: "Mon & Wed 10-11AM" or "Tue & Thu 9-10:30AM"
            '/^(Mon|Tue|Wed|Thu|Fri)(?:\s*&\s*(Mon|Tue|Wed|Thu|Fri))?\s+(\d{1,2}(?::\d{2})?)-(\d{1,2}(?::\d{2})?)(AM|PM|NN)$/i',
            // Pattern 2: "MWF 8:00-9:00 AM" (original format)
            '/^([MTWFH]+)\s+(\d{1,2}:\d{2})-(\d{1,2}:\d{2})\s*(AM|PM)$/i',
            // Pattern 3: Single day "Mon 9:00-10:30AM"
            '/^(Mon|Tue|Wed|Thu|Fri)\s+(\d{1,2}(?::\d{2})?)-(\d{1,2}(?::\d{2})?)(AM|PM|NN)$/i',
            // Pattern 4: Handle specific formats like "Wed 10:30-12:00PM"
            '/^(Mon|Tue|Wed|Thu|Fri)\s+(\d{1,2}:\d{2})-(1[0-2]:\d{2})(PM)$/i'
        ];
        
        $matches = null;
        $patternUsed = 0;
        
        foreach ($patterns as $index => $pattern) {
            if (preg_match($pattern, $timeString, $matches)) {
                $patternUsed = $index + 1;
                break;
            }
        }
        
        if (!$matches) {
            throw new Exception("Invalid time format: $timeString");
        }
        
        // Extract components based on pattern used
        if ($patternUsed == 1) { // "Mon & Wed 10-11AM"
            $firstDay = $matches[1];
            $secondDay = $matches[2] ?? null;
            $startTime = $matches[3];
            $endTime = $matches[4];
            $period = $matches[5];
            $days = $this->convertDayNames($firstDay . ($secondDay ? ' & ' . $secondDay : ''));
        } elseif ($patternUsed == 2) { // "MWF 8:00-9:00 AM"
            $days = $matches[1];
            $startTime = $matches[2];
            $endTime = $matches[3];
            $period = $matches[4] ?? 'AM';
        } elseif ($patternUsed == 3) { // Pattern 3: Single day "Mon 9:00-10:30AM"
            $days = $this->convertDayNames($matches[1]);
            $startTime = $matches[2];
            $endTime = $matches[3];
            $period = $matches[4];
        } else { // Pattern 4: Handle specific formats like "Wed 10:30-12:00PM"
            $days = $this->convertDayNames($matches[1]);
            $startTime = $matches[2];
            $endTime = $matches[3];
            $period = $matches[4];
        }
        
        // Add colons to time if missing
        $startTime = $this->addColonsToTime($startTime);
        $endTime = $this->addColonsToTime($endTime);
        
        // Handle special cases for time periods
        $startPeriod = $period;
        $endPeriod = $period;
        
        // Special handling for "NN" (noon) and time ranges crossing AM/PM
        if ($period === 'NN') {
            // For "9-12NN", start is 9 AM, end is 12 PM (noon)
            $startPeriod = 'AM';
            $endPeriod = 'PM';
        } else {
            // Check if we need to handle time ranges that cross AM/PM boundary
            $startHour = intval(explode(':', $startTime)[0]);
            $endHour = intval(explode(':', $endTime)[0]);
            
            // If end time is smaller number than start time in same period, 
            // it likely crosses the boundary (e.g., 10 PM - 2 AM would be next day)
            // But for our class schedules, this is unlikely, so we'll assume same period
        }
        
        // Get the target time with appropriate period
        if ($isStart) {
            $targetTime = $this->convertTo24Hour($startTime, $startPeriod);
        } else {
            $targetTime = $this->convertTo24Hour($endTime, $endPeriod);
        }
        
        // Validate time range (only when getting end time)
        if (!$isStart) {
            $startTime24 = $this->convertTo24Hour($startTime, $startPeriod);
            $endTime24 = $this->convertTo24Hour($endTime, $endPeriod);
            
            if ($endTime24 <= $startTime24) {
                throw new Exception("Invalid time range: end time ($endTime $endPeriod = $endTime24) must be after start time ($startTime $startPeriod = $startTime24) for schedule: $timeString");
            }
        }
        
        // Find the next occurrence of the first day in the schedule
        $nextDate = $this->getNextClassDate($days);
        
        return $nextDate->format('Y-m-d') . 'T' . $targetTime . ':00';
    }
    
    /**
     * Convert full day names to single letters
     */
    private function convertDayNames($dayString) {
        $dayMap = [
            'Mon' => 'M',
            'Tue' => 'T', 
            'Wed' => 'W',
            'Thu' => 'H', // Use H for Thursday to distinguish from Tuesday
            'Fri' => 'F'
        ];
        
        $result = '';
        foreach ($dayMap as $fullName => $letter) {
            if (strpos($dayString, $fullName) !== false) {
                $result .= $letter;
            }
        }
        
        return $result ?: 'M'; // Default to Monday if no match
    }
    
    /**
     * Add colons to time format if missing
     */
    private function addColonsToTime($time) {
        // If time doesn't have colon and is just hour, add :00
        if (!strpos($time, ':') && is_numeric($time)) {
            return $time . ':00';
        }
        return $time;
    }
    
    /**
     * Convert 12-hour time to 24-hour format
     */
    private function convertTo24Hour($time, $period) {
        list($hour, $minute) = explode(':', $time);
        $hour = intval($hour);
        
        if ($period === 'PM' && $hour !== 12) {
            $hour += 12;
        } elseif ($period === 'AM' && $hour === 12) {
            $hour = 0;
        }
        
        return sprintf('%02d:%02d', $hour, $minute);
    }
    
    /**
     * Get next class date based on schedule
     */
    private function getNextClassDate($dayString) {
        $dayMap = ['M' => 1, 'T' => 2, 'W' => 3, 'H' => 4, 'F' => 5]; // Monday=1, Thursday=4, Friday=5
        
        $currentDate = new DateTime();
        $targetDay = $dayMap[$dayString[0]] ?? 1; // Use first day
        
        // Find next occurrence of target day
        while ($currentDate->format('N') != $targetDay) {
            $currentDate->add(new DateInterval('P1D'));
        }
        
        return $currentDate;
    }
    
    /**
     * Generate recurrence rule for weekly classes
     */
    private function generateRecurrenceRule($timeString) {
        // Parse the time string to get days
        $patterns = [
            '/^(Mon|Tue|Wed|Thu|Fri)(?:\s*&\s*(Mon|Tue|Wed|Thu|Fri))?\s+/',
            '/^([MTWF]+)\s+/',
            '/^(Mon|Tue|Wed|Thu|Fri)\s+/'
        ];
        
        $days = '';
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $timeString, $matches)) {
                if (isset($matches[2])) {
                    // Handle "Mon & Wed" format
                    $days = $this->convertDayNames($matches[1] . ' & ' . $matches[2]);
                } else {
                    // Handle single format
                    $days = strpos($matches[1], ' ') === false ? $matches[1] : $this->convertDayNames($matches[1]);
                }
                break;
            }
        }
        
        if (!$days) {
            $days = 'MWF'; // Default fallback
        }
        
        $weekDays = [];
        for ($i = 0; $i < strlen($days); $i++) {
            switch ($days[$i]) {
                case 'M': $weekDays[] = 'MO'; break;
                case 'T': $weekDays[] = 'TU'; break;
                case 'W': $weekDays[] = 'WE'; break;
                case 'H': $weekDays[] = 'TH'; break; // Thursday
                case 'F': $weekDays[] = 'FR'; break;
            }
        }
        
        // Create recurrence for the entire semester
        $semesterEnd = new DateTime(ACADEMIC_YEAR_END);
        
        return 'RRULE:FREQ=WEEKLY;BYDAY=' . implode(',', $weekDays) . ';UNTIL=' . $semesterEnd->format('Ymd\THis\Z');
    }
    
    /**
     * Clear existing events for current semester
     */
    private function clearExistingEvents($calendarId) {
        $timeMin = (new DateTime(ACADEMIC_YEAR_START))->format('c');
        $timeMax = (new DateTime(ACADEMIC_YEAR_END))->format('c');
        
        $events = $this->service->events->listEvents($calendarId, [
            'timeMin' => $timeMin,
            'timeMax' => $timeMax,
            'q' => 'EVSU'
        ]);
        
        foreach ($events->getItems() as $event) {
            $this->service->events->delete($calendarId, $event->getId());
        }
    }
    
    /**
     * Get student schedule from database
     */
    private function getStudentSchedule() {
        require_once 'evsu_api_supabase.php';
        
        $studentData = getStudentEnrollmentData($this->studentId);
        return $studentData['schedule'] ?? [];
    }
    
    /**
     * Store access token for student
     */
    private function storeAccessToken($token) {
        $tokenFile = __DIR__ . "/../tokens/google_token_{$this->studentId}.json";
        
        // Create tokens directory if it doesn't exist
        $tokenDir = dirname($tokenFile);
        if (!is_dir($tokenDir)) {
            mkdir($tokenDir, 0755, true);
        }
        
        file_put_contents($tokenFile, json_encode($token));
    }
    
    /**
     * Get stored access token for student
     */
    private function getStoredAccessToken() {
        $tokenFile = __DIR__ . "/../tokens/google_token_{$this->studentId}.json";
        
        if (file_exists($tokenFile)) {
            return json_decode(file_get_contents($tokenFile), true);
        }
        
        return null;
    }
    
    /**
     * Get redirect URI for OAuth
     */
    private function getRedirectUri() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        return $protocol . '://' . $host . '/student_portal/google_calendar_callback.php';
    }
    
    /**
     * Get portal URL
     */
    private function getPortalUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        return $protocol . '://' . $host . '/student_portal/';
    }
    
    /**
     * Revoke Google Calendar authorization
     */
    public function revokeAuthorization() {
        $token = $this->getStoredAccessToken();
        if ($token) {
            $this->client->revokeToken($token);
            
            // Delete stored token
            $tokenFile = __DIR__ . "/../tokens/google_token_{$this->studentId}.json";
            if (file_exists($tokenFile)) {
                unlink($tokenFile);
            }
        }
    }
}