<?php
/**
 * Supabase API wrapper for EVSU Student Portal
 * Handles all communication with the enrollment system
 */

class SupabaseAPI {
    private $supabaseUrl;
    private $supabaseKey;
    private $headers;

    public function __construct() {
        // TODO: Replace with your actual Supabase credentials
        $this->supabaseUrl = 'https://qxcwdgcexuwjdrgfzxnd.supabase.co';
        $this->supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InF4Y3dkZ2NleHV3amRyZ2Z6eG5kIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDg3ODIyODIsImV4cCI6MjA2NDM1ODI4Mn0.PvSAK7iZwD-u6atl3r4ZvnVRfbvgC6gP8MLoG5EQcxQ';
        
        $this->headers = [
            'apikey: ' . $this->supabaseKey,
            'Authorization: Bearer ' . $this->supabaseKey,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ];
    }

    /**
     * Generic method to make cURL requests to Supabase
     */
    private function makeRequest($endpoint, $method = 'GET', $data = null, $params = []) {
        $url = $this->supabaseUrl . $endpoint;
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'PATCH':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            error_log("Supabase API Error: cURL failed");
            return null;
        }

        $decodedResponse = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return $decodedResponse;
        } else {
            error_log("Supabase API Error: HTTP $httpCode - " . $response);
            return null;
        }
    }

    /**
     * Get student information by student ID
     */
    public function getStudentInfo($studentId) {
        $params = [
            'student_id' => 'eq.' . $studentId,
            'select' => '*'
        ];
        
        $response = $this->makeRequest('/students', 'GET', null, $params);
        return $response ? (count($response) > 0 ? $response[0] : null) : null;
    }

    /**
     * Get student enrollments with course and subject details
     */
    public function getStudentEnrollments($studentId) {
        $params = [
            'student_id' => 'eq.' . $studentId,
            'select' => '*, courses(*), subjects(*)',
            'order' => 'created_at.desc'
        ];
        
        return $this->makeRequest('/enrollments', 'GET', null, $params);
    }

    /**
     * Get student's current schedule
     */
    public function getStudentSchedule($studentId) {
        // Assuming you have a schedules table or can derive schedule from enrollments
        $params = [
            'student_id' => 'eq.' . $studentId,
            'select' => '*, subjects(*, courses(*))',
            'is_active' => 'eq.true'
        ];
        
        return $this->makeRequest('/schedules', 'GET', null, $params);
    }

    /**
     * Get all available courses
     */
    public function getCourses() {
        $params = [
            'select' => '*',
            'order' => 'course_name.asc'
        ];
        
        return $this->makeRequest('/courses', 'GET', null, $params);
    }

    /**
     * Get subjects for a specific course
     */
    public function getSubjectsByCourse($courseId) {
        $params = [
            'course_id' => 'eq.' . $courseId,
            'select' => '*',
            'order' => 'subject_name.asc'
        ];
        
        return $this->makeRequest('/subjects', 'GET', null, $params);
    }

    /**
     * Check if student exists in enrollment system
     */
    public function studentExists($studentId) {
        $student = $this->getStudentInfo($studentId);
        return $student !== null;
    }

    /**
     * Check if student is currently enrolled
     */
    public function isStudentEnrolled($studentId) {
        $enrollments = $this->getStudentEnrollments($studentId);
        
        if (!$enrollments) return false;
        
        // Check if student has active enrollments
        foreach ($enrollments as $enrollment) {
            if (isset($enrollment['is_active']) && $enrollment['is_active']) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get student's grades
     */
    public function getStudentGrades($studentId) {
        $params = [
            'student_id' => 'eq.' . $studentId,
            'select' => '*, subjects(*), courses(*)',
            'order' => 'semester.desc,year.desc'
        ];
        
        return $this->makeRequest('/grades', 'GET', null, $params);
    }

    /**
     * Update student information
     */
    public function updateStudentInfo($studentId, $data) {
        $params = ['student_id' => 'eq.' . $studentId];
        return $this->makeRequest('/students', 'PATCH', $data, $params);
    }
}
?>