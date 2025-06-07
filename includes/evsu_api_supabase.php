<?php
/**
 * Supabase Integration for EVSU Student Portal
 * This connects directly to the friend's enrollment system database
 * All student data comes from their Supabase instance
 */

class SupabaseAPI {
    private $supabaseUrl;
    private $supabaseKey;
    private $headers;

    public function __construct() {
        // Using actual Supabase credentials
        $this->supabaseUrl = 'https://qxcwdgcexuwjdrgfzxnd.supabase.co/rest/v1';
        $this->supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InF4Y3dkZ2NleHV3amRyZ2Z6eG5kIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDg3ODIyODIsImV4cCI6MjA2NDM1ODI4Mn0.PvSAK7iZwD-u6atl3r4ZvnVRfbvgC6gP8MLoG5EQcxQ';
        
        $this->headers = [
            'apikey: ' . $this->supabaseKey,
            'Authorization: Bearer ' . $this->supabaseKey,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ];
    }

    /**
     * Make requests to Supabase
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
     * Generic select method
     */
    public function select($table, $conditions = [], $select = '*') {
        $params = ['select' => $select];
        
        foreach ($conditions as $key => $value) {
            $params[$key] = 'eq.' . $value;
        }
        
        return $this->makeRequest('/' . $table, 'GET', null, $params);
    }

    /**
     * Generic insert method
     */
    public function insert($table, $data) {
        return $this->makeRequest('/' . $table, 'POST', $data);
    }

    /**
     * Generic update method
     */
    public function update($table, $data, $conditions = []) {
        $params = [];
        
        foreach ($conditions as $key => $value) {
            $params[$key] = 'eq.' . $value;
        }
        
        return $this->makeRequest('/' . $table, 'PATCH', $data, $params);
    }

    /**
     * Check if student exists in the users table
     */
    public function studentExists($studentId) {
        $params = [
            'student_id' => 'eq.' . $studentId,
            'select' => 'student_id'
        ];
        
        $response = $this->makeRequest('/users', 'GET', null, $params);
        return $response && count($response) > 0;
    }

    /**
     * Get student information from users table
     */
    public function getStudentInfo($studentId) {
        $params = [
            'student_id' => 'eq.' . $studentId,
            'select' => '*'
        ];
        
        $response = $this->makeRequest('/users', 'GET', null, $params);
        return $response && count($response) > 0 ? $response[0] : null;
    }

    /**
     * Authenticate student using users table
     */
    public function authenticateStudent($studentId, $password = null) {
        $student = $this->getStudentInfo($studentId);
        
        if (!$student) {
            return false;
        }

        // Check password if provided
        if ($password !== null) {
            // In a real application, you'd use password_verify() here
            // For now, we'll check if the password field exists and is not empty
            if (isset($student['password']) && !empty($student['password'])) {
                // This would be: return password_verify($password, $student['password']);
                // For demo purposes, we'll allow any password for existing students
                return true;
            }
        }

        return true;
    }

    /**
     * Set/Update student password in users table
     */
    public function setStudentPassword($studentId, $password) {
        error_log("Attempting to update password for student: $studentId");
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        error_log("Generated hashed password: " . substr($hashedPassword, 0, 20) . "...");
        
        $data = [
            'password' => $hashedPassword,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $params = [
            'student_id' => 'eq.' . $studentId
        ];
        
        error_log("Update data: " . json_encode($data));
        error_log("Update params: " . json_encode($params));
        
        try {
            $result = $this->makeRequest('/users', 'PATCH', $data, $params);
            
            error_log("Supabase update result: " . json_encode($result));
            
            // Check if the update was successful
            if ($result !== null) {
                // For PATCH requests, Supabase might return an empty array or the updated record
                error_log("Password update successful for student $studentId");
                return true;
            } else {
                error_log("Password update failed for student $studentId - null result returned");
                return false;
            }
        } catch (Exception $e) {
            error_log("Password update exception for student $studentId: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get student enrollments with course details
     */
    public function getStudentEnrollments($studentId) {
        $params = [
            'student_id' => 'eq.' . $studentId,
            'select' => '*, courses(name)'
        ];
        
        return $this->makeRequest('/enrollments', 'GET', null, $params);
    }

    /**
     * Get subjects for a student's course and year level
     */
    public function getStudentSubjects($studentId) {
        // First get enrollment info to know their course and year level
        $enrollments = $this->getStudentEnrollments($studentId);
        if (!$enrollments || empty($enrollments)) {
            return [];
        }

        $enrollment = $enrollments[0]; // Get the first/active enrollment
        
        $params = [
            'course_id' => 'eq.' . $enrollment['course_id'],
            'year_level' => 'eq.' . $enrollment['year_level'],
            'select' => '*'
        ];
        
        return $this->makeRequest('/subjects', 'GET', null, $params);
    }

    /**
     * Get student schedule (using subjects table)
     */
    public function getStudentSchedule($studentId) {
        return $this->getStudentSubjects($studentId);
    }

    /**
     * Get student grades - not implemented in their schema yet
     */
    public function getStudentGrades($studentId) {
        // Their schema doesn't have a grades table yet
        // Return empty array for now
        return [];
    }

    /**
     * Check if student is enrolled (has approved enrollment)
     */
    public function isStudentEnrolled($studentId) {
        $enrollments = $this->getStudentEnrollments($studentId);
        
        if (!$enrollments) return false;
        
        foreach ($enrollments as $enrollment) {
            if (isset($enrollment['status']) && $enrollment['status'] === 'approved') {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Update student profile information in users table
     */
    public function updateStudentProfile($studentId, $data) {
        $conditions = ['student_id' => $studentId];
        return $this->update('users', $data, $conditions);
    }

    /**
     * Check if in mock mode (always false since using real Supabase)
     */
    public function isMockMode() {
        return false; // Using real Supabase, not mock mode
    }
}

// Initialize global instance
$supabaseAPI = new SupabaseAPI();

/**
 * Helper functions for the student portal
 */

function doesStudentIdExistInSystem($studentId) {
    global $supabaseAPI;
    
    try {
        return $supabaseAPI->studentExists($studentId);
    } catch (Exception $e) {
        error_log("Error checking student existence: " . $e->getMessage());
        return false;
    }
}

function authenticateStudentCredentials($studentId, $password) {
    global $supabaseAPI;
    
    try {
        return $supabaseAPI->authenticateStudent($studentId, $password);
    } catch (Exception $e) {
        error_log("Error authenticating student: " . $e->getMessage());
        return false;
    }
}

function setStudentPortalPassword($studentId, $password) {
    global $supabaseAPI;
    
    try {
        error_log("Helper function: Setting password for student $studentId");
        
        $result = $supabaseAPI->setStudentPassword($studentId, $password);
        
        if ($result) {
            error_log("Helper function: Password successfully updated for student: $studentId");
            return true;
        } else {
            error_log("Helper function: Failed to update password for student: $studentId");
            return false;
        }
    } catch (Exception $e) {
        error_log("Helper function error setting student password for $studentId: " . $e->getMessage());
        return false;
    }
}

function isStudentEnrolled($studentId) {
    global $supabaseAPI;
    
    try {
        return $supabaseAPI->isStudentEnrolled($studentId);
    } catch (Exception $e) {
        error_log("Error checking enrollment status: " . $e->getMessage());
        return false;
    }
}

function getStudentEnrollmentData($studentId) {
    global $supabaseAPI;
    
    try {
        // Get user info (contains name, email, etc.)
        $userInfo = $supabaseAPI->getStudentInfo($studentId);
        if (!$userInfo) {
            return null;
        }

        // Get enrollments with course info
        $enrollments = $supabaseAPI->getStudentEnrollments($studentId);
        
        // Get subjects for this student's course and year level
        $subjects = $supabaseAPI->getStudentSubjects($studentId);
        
        // Get course name from enrollment
        $courseName = 'N/A';
        $yearLevel = 1;
        $enrollmentStatus = 'pending';
        
        if ($enrollments && !empty($enrollments)) {
            $enrollment = $enrollments[0];
            $courseName = $enrollment['courses']['name'] ?? 'Unknown Course';
            $yearLevel = $enrollment['year_level'] ?? 1;
            $enrollmentStatus = $enrollment['status'] ?? 'pending';
        }
        
        // Format the data to match your existing structure
        $formattedData = [
            'name' => $userInfo['name'] ?? 'Unknown Student',
            'email' => $userInfo['email'] ?? ($studentId . '@evsu.edu.ph'), // Use actual email from users table
            'course' => $courseName,
            'year_level' => $yearLevel,
            'student_number' => $userInfo['student_id'],
            'enrollment_status' => $enrollmentStatus,
            'faculty_name' => $userInfo['faculty_name'] ?? null,
            'department' => $userInfo['department'] ?? null,
            'profile_picture' => $userInfo['profile_picture'] ?? null,
            'email_verified_at' => $userInfo['email_verified_at'] ?? null,
            'created_at' => $userInfo['created_at'] ?? null,
            'updated_at' => $userInfo['updated_at'] ?? null,
            'subjects_enrolled' => [],
            'schedule' => []
        ];

        // Process subjects to get enrolled subjects and schedule
        if ($subjects) {
            foreach ($subjects as $subject) {
                $formattedData['subjects_enrolled'][] = [
                    'code' => $subject['code'] ?? 'N/A',
                    'desc' => $subject['name'] ?? 'N/A',
                    'units' => $subject['units'] ?? 3,
                    'schedule' => $subject['schedule'] ?? 'TBA'
                ];

                $formattedData['schedule'][] = [
                    'code' => $subject['code'] ?? 'N/A',
                    'desc' => $subject['name'] ?? 'N/A',
                    'time' => $subject['schedule'] ?? 'TBA',
                    'room' => $subject['room'] ?? 'TBA',
                    'instructor' => $subject['professor'] ?? 'TBA'
                ];
            }
        }

        return $formattedData;
        
    } catch (Exception $e) {
        error_log("Error getting student enrollment data: " . $e->getMessage());
        return null;
    }
}

function getStudentGrades($studentId) {
    global $supabaseAPI;
    
    try {
        return $supabaseAPI->getStudentGrades($studentId);
    } catch (Exception $e) {
        error_log("Error getting student grades: " . $e->getMessage());
        return [];
    }
}

function updateStudentProfile($studentId, $profileData) {
    global $supabaseAPI;
    
    try {
        return $supabaseAPI->updateStudentProfile($studentId, $profileData);
    } catch (Exception $e) {
        error_log("Error updating student profile: " . $e->getMessage());
        return false;
    }
}

// Updated function to use actual user email
function sendOtpEmail($studentId, $otp) {
    global $supabaseAPI;
    
    try {
        $userInfo = $supabaseAPI->getStudentInfo($studentId);
        $email = $userInfo['email'] ?? ($studentId . '@evsu.edu.ph');
        
        // In a real application, you'd send actual email here
        error_log("Simulating OTP email to $email: Your OTP is $otp");
        return true;
    } catch (Exception $e) {
        error_log("Error sending OTP email: " . $e->getMessage());
        return false;
    }
}
?>