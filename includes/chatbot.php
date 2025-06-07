<?php
// filepath: c:\xampp\htdocs\student_portal\includes\chatbot.php
class StudentPortalChatbot {
    private $witToken;
    private $studentId;
    
    public function __construct($studentId) {
        $this->witToken = WIT_AI_TOKEN;
        $this->studentId = $studentId;
    }
    
    public function processMessage($message) {
        // Send message to Wit.ai for intent recognition
        $witResponse = $this->sendToWit($message);
        
        // Process the response and generate appropriate reply
        return $this->generateResponse($witResponse, $message);
    }
    
    private function sendToWit($message) {
        $url = "https://api.wit.ai/message?v=20230215&q=" . urlencode($message);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->witToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("Wit.ai API Error: HTTP $httpCode - $response");
            return null;
        }
        
        return json_decode($response, true);
    }
    
    private function generateResponse($witResponse, $originalMessage) {
        if (!$witResponse || empty($witResponse['intents'])) {
            return $this->getDefaultResponse($originalMessage);
        }
        
        $intent = $witResponse['intents'][0]['name'] ?? 'unknown';
        $confidence = $witResponse['intents'][0]['confidence'] ?? 0;
        
        // Extract entities
        $entities = $witResponse['entities'] ?? [];
        
        if ($confidence < 0.5) {
            return $this->getHelpResponse();
        }
        
        switch ($intent) {
            case 'get_grades':
                return $this->getGradesResponse($entities);
            case 'get_schedule':
                return $this->getScheduleResponse($entities);
            case 'get_subjects':
                return $this->getSubjectsResponse();
            case 'enrollment_info':
                return $this->getEnrollmentResponse();
            case 'personal_info':
                return $this->getPersonalInfoResponse();
            case 'greetings':
                return $this->getGreetingResponse();
            case 'help':
                return $this->getHelpResponse();
            case 'get_gpa':
                return $this->getGPAResponse();
            case 'get_units':
                return $this->getUnitsResponse();
            case 'change_password':
                return $this->getPasswordChangeResponse();
            default:
                return $this->getDefaultResponse($originalMessage);
        }
    }
    
    private function getGradesResponse($entities) {
        try {
            // Check if specific subject is mentioned
            $specificSubject = null;
            if (isset($entities['subject:subject'])) {
                $specificSubject = $entities['subject:subject'][0]['value'] ?? null;
            }
            
            $grades = getStudentGrades($this->studentId);
            
            if (empty($grades)) {
                return "📚 You don't have any grades posted yet. Check back later or contact your instructor.\n\n📧 Need help? Contact your academic advisor.";
            }
            
            if ($specificSubject) {
                // Filter for specific subject
                $filteredGrades = array_filter($grades, function($grade) use ($specificSubject) {
                    return stripos($grade['subject_name'], $specificSubject) !== false || 
                           stripos($grade['subject_code'], $specificSubject) !== false;
                });
                
                if (empty($filteredGrades)) {
                    return "📚 No grades found for '$specificSubject'. Try asking about a different subject or check your enrolled subjects.";
                }
                
                $grades = $filteredGrades;
            }
            
            $response = "📊 **Your Academic Grades:**\n\n";
            foreach (array_slice($grades, 0, 8) as $grade) {
                $gradeIcon = $this->getGradeIcon($grade['grade']);
                $response .= "$gradeIcon **{$grade['subject_code']}** - {$grade['subject_name']}\n";
                $response .= "   Grade: **{$grade['grade']}** | Units: {$grade['units']}\n\n";
            }
            
            if (count($grades) > 8) {
                $response .= "📖 *Showing recent grades. [View All Grades](grades.php)*";
            }
            
            return $response;
            
        } catch (Exception $e) {
            return "❌ Sorry, I couldn't retrieve your grades right now. Please try accessing the [Grades Page](grades.php) directly.";
        }
    }
    
    private function getScheduleResponse($entities) {
        try {
            // Check for specific day
            $specificDay = null;
            if (isset($entities['datetime:datetime'])) {
                $datetime = $entities['datetime:datetime'][0]['value'] ?? null;
                if ($datetime) {
                    $specificDay = date('l', strtotime($datetime));
                }
            }
            
            $studentData = getStudentEnrollmentData($this->studentId);
            
            if (empty($studentData['subjects_enrolled'])) {
                return "📅 You don't have any subjects enrolled for this semester.\n\n📝 [Check Enrollment](enrollment.php)";
            }
            
            $response = "🕐 **Your Class Schedule:**\n\n";
            
            foreach ($studentData['subjects_enrolled'] as $subject) {
                $scheduleIcon = "📚";
                $response .= "$scheduleIcon **{$subject['code']}** - {$subject['desc']}\n";
                
                if (isset($subject['schedule']) && !empty($subject['schedule'])) {
                    $response .= "   ⏰ {$subject['schedule']}\n";
                } else {
                    $response .= "   ⏰ Schedule TBA\n";
                }
                
                if (isset($subject['room']) && !empty($subject['room'])) {
                    $response .= "   🏫 Room: {$subject['room']}\n";
                }
                
                $response .= "\n";
            }
            
            $response .= "📋 [View Detailed Schedule](schedule.php)";
            return $response;
            
        } catch (Exception $e) {
            return "❌ Sorry, I couldn't retrieve your schedule right now. Please try accessing the [Schedule Page](schedule.php) directly.";
        }
    }
    
    private function getSubjectsResponse() {
        try {
            $studentData = getStudentEnrollmentData($this->studentId);
            $subjects = $studentData['subjects_enrolled'] ?? [];
            $totalUnits = array_sum(array_column($subjects, 'units'));
            
            if (empty($subjects)) {
                return "📖 You're not enrolled in any subjects this semester.\n\n📝 [Start Enrollment](enrollment.php)";
            }
            
            $response = "📚 **Your Enrolled Subjects** (" . count($subjects) . " subjects):\n\n";
            
            foreach ($subjects as $index => $subject) {
                $number = $index + 1;
                $response .= "$number. **{$subject['code']}** - {$subject['desc']}\n";
                $response .= "   📊 Units: {$subject['units']} | Status: ✅ Enrolled\n\n";
            }
            
            $response .= "📈 **Summary:**\n";
            $response .= "• Total Subjects: **" . count($subjects) . "**\n";
            $response .= "• Total Units: **{$totalUnits}**\n\n";
            $response .= "📚 [View Subject Details](subjects.php)";
            
            return $response;
            
        } catch (Exception $e) {
            return "❌ Sorry, I couldn't retrieve your subjects right now. Please try accessing the [Subjects Page](subjects.php) directly.";
        }
    }
    
    private function getEnrollmentResponse() {
        try {
            $isOpen = isEnrollmentOpen();
            $academicYear = getCurrentAcademicYear();
            $semester = getCurrentSemester();
            
            $response = "📝 **Enrollment Information:**\n\n";
            $response .= "🗓️ **Period:** {$semester} {$academicYear}\n";
            
            if ($isOpen) {
                $response .= "✅ **Status:** OPEN\n\n";
                $response .= "🎯 **What you can do:**\n";
                $response .= "• Add new subjects\n";
                $response .= "• Drop subjects\n";
                $response .= "• Modify your enrollment\n\n";
                $response .= "📝 [Go to Enrollment Portal](enrollment.php)";
            } else {
                $response .= "❌ **Status:** CLOSED\n\n";
                $response .= "📞 **Need help?** Contact the registrar's office:\n";
                $response .= "• Email: registrar@evsu.edu.ph\n";
                $response .= "• Phone: (053) 321-8611\n\n";
                $response .= "⚠️ Late enrollment may require special permission.";
            }
            
            return $response;
            
        } catch (Exception $e) {
            return "❌ Sorry, I couldn't retrieve enrollment information right now.";
        }
    }
    
    private function getPersonalInfoResponse() {
        try {
            global $supabaseAPI;
            $studentInfo = $supabaseAPI->getStudentInfo($this->studentId);
            
            if (!$studentInfo) {
                return "❌ Sorry, I couldn't retrieve your profile information right now.";
            }
            
            $response = "👤 **Your Profile Information:**\n\n";
            $response .= "🆔 **Student ID:** {$this->studentId}\n";
            $response .= "👨‍🎓 **Name:** {$studentInfo['name']}\n";
            $response .= "📧 **Email:** {$studentInfo['email']}\n";
            $response .= "🎓 **Role:** " . ucfirst($studentInfo['role']) . "\n";
            
            if (isset($studentInfo['course'])) {
                $response .= "📚 **Course:** {$studentInfo['course']}\n";
            }
            
            $response .= "\n✏️ [Update Profile](profile.php)\n";
            $response .= "🔐 [Change Password](change_password.php)";
            
            return $response;
                   
        } catch (Exception $e) {
            return "❌ Sorry, I couldn't retrieve your profile information right now.";
        }
    }
    
    private function getGPAResponse() {
        try {
            $grades = getStudentGrades($this->studentId);
            
            if (empty($grades)) {
                return "📊 No grades available to calculate GPA yet.";
            }
            
            // Calculate GPA (simplified)
            $totalPoints = 0;
            $totalUnits = 0;
            $gradeCount = 0;
            
            foreach ($grades as $grade) {
                $gradePoint = $this->convertGradeToPoint($grade['grade']);
                if ($gradePoint !== null) {
                    $units = $grade['units'] ?? 3;
                    $totalPoints += $gradePoint * $units;
                    $totalUnits += $units;
                    $gradeCount++;
                }
            }
            
            if ($totalUnits > 0) {
                $gpa = $totalPoints / $totalUnits;
                $response = "📊 **Your Academic Performance:**\n\n";
                $response .= "🎯 **Current GPA:** " . number_format($gpa, 2) . "\n";
                $response .= "📚 **Graded Subjects:** {$gradeCount}\n";
                $response .= "📈 **Total Units:** {$totalUnits}\n\n";
                $response .= $this->getGPAAdvice($gpa);
                return $response;
            } else {
                return "📊 GPA calculation unavailable. No numeric grades found.";
            }
            
        } catch (Exception $e) {
            return "❌ Sorry, I couldn't calculate your GPA right now.";
        }
    }
    
    private function getUnitsResponse() {
        try {
            $studentData = getStudentEnrollmentData($this->studentId);
            $subjects = $studentData['subjects_enrolled'] ?? [];
            $totalUnits = array_sum(array_column($subjects, 'units'));
            
            $response = "📊 **Your Unit Summary:**\n\n";
            $response .= "📚 **Current Semester:** {$totalUnits} units\n";
            $response .= "🎯 **Enrolled Subjects:** " . count($subjects) . "\n\n";
            
            if ($totalUnits < 18) {
                $response .= "💡 **Note:** You're taking a light load. Consider enrolling in more subjects if needed.\n";
            } elseif ($totalUnits > 24) {
                $response .= "⚠️ **Note:** You're taking a heavy load. Make sure you can handle the workload.\n";
            } else {
                $response .= "✅ **Note:** You have a balanced course load.\n";
            }
            
            $response .= "\n📖 [View Subjects](subjects.php)";
            
            return $response;
            
        } catch (Exception $e) {
            return "❌ Sorry, I couldn't retrieve your unit information right now.";
        }
    }
    
    private function getPasswordChangeResponse() {
        return "🔐 **Change Your Password:**\n\n" .
               "For security reasons, I can't change your password directly through chat.\n\n" .
               "🔗 Please use the secure password change form:\n" .
               "[Change Password](change_password.php)\n\n" .
               "💡 **Password Tips:**\n" .
               "• Use at least 8 characters\n" .
               "• Include uppercase and lowercase letters\n" .
               "• Add numbers and special characters\n" .
               "• Don't reuse old passwords";
    }
    
    private function getGreetingResponse() {
        $hour = date('H');
        $timeGreeting = "Hello";
        
        if ($hour < 12) {
            $timeGreeting = "Good morning";
        } elseif ($hour < 17) {
            $timeGreeting = "Good afternoon";
        } else {
            $timeGreeting = "Good evening";
        }
        
        $greetings = [
            "$timeGreeting! 👋 I'm your EVSU Student Portal assistant. How can I help you today?",
            "Hi there! 😊 What would you like to know about your academic information?",
            "$timeGreeting! 🎓 I'm here to help with your student portal questions.",
            "Hey! 🤖 Ready to help you with your academic queries!"
        ];
        
        return $greetings[array_rand($greetings)];
    }
    
    private function getHelpResponse() {
        return "🤖 **I can help you with:**\n\n" .
               "📚 **Grades** - \"What are my grades?\" or \"Show my GPA\"\n" .
               "📅 **Schedule** - \"What's my schedule?\" or \"When is my next class?\"\n" .
               "📖 **Subjects** - \"What subjects am I taking?\"\n" .
               "📝 **Enrollment** - \"Is enrollment open?\" or \"Can I enroll?\"\n" .
               "👤 **Profile** - \"What's my student info?\"\n" .
               "📊 **Units** - \"How many units am I taking?\"\n" .
               "🔐 **Password** - \"How do I change my password?\"\n\n" .
               "💡 **Just ask me naturally!** For example:\n" .
               "• \"What time is my math class?\"\n" .
               "• \"How's my academic performance?\"\n" .
               "• \"Can I still add subjects?\"";
    }
    
    private function getDefaultResponse($message) {
        return "🤔 I'm not sure I understand that question.\n\n" .
               "**Try asking about:**\n" .
               "• Your grades or academic performance\n" .
               "• Your class schedule or timetable\n" .
               "• Enrolled subjects and units\n" .
               "• Enrollment status and deadlines\n" .
               "• Your profile information\n\n" .
               "💡 Type **'help'** for a complete list of what I can do!";
    }
    
    // Helper methods
    private function getGradeIcon($grade) {
        if (is_numeric($grade)) {
            $numGrade = floatval($grade);
            if ($numGrade >= 90) return "🏆";
            if ($numGrade >= 80) return "🥇";
            if ($numGrade >= 70) return "🥈";
            if ($numGrade >= 60) return "🥉";
            return "📉";
        }
        
        switch (strtoupper($grade)) {
            case 'A': case '1.0': case '1.25': return "🏆";
            case 'B': case '1.5': case '1.75': case '2.0': return "🥇";
            case 'C': case '2.25': case '2.5': case '2.75': return "🥈";
            case 'D': case '3.0': return "🥉";
            case 'F': case '5.0': return "📉";
            default: return "📊";
        }
    }
    
    private function convertGradeToPoint($grade) {
        if (is_numeric($grade)) {
            $numGrade = floatval($grade);
            if ($numGrade >= 97) return 4.0;
            if ($numGrade >= 93) return 3.7;
            if ($numGrade >= 90) return 3.3;
            if ($numGrade >= 87) return 3.0;
            if ($numGrade >= 83) return 2.7;
            if ($numGrade >= 80) return 2.3;
            if ($numGrade >= 77) return 2.0;
            if ($numGrade >= 73) return 1.7;
            if ($numGrade >= 70) return 1.3;
            if ($numGrade >= 67) return 1.0;
            return 0.0;
        }
        
        // Handle letter grades
        switch (strtoupper($grade)) {
            case 'A': return 4.0;
            case 'B': return 3.0;
            case 'C': return 2.0;
            case 'D': return 1.0;
            case 'F': return 0.0;
            case '1.0': return 4.0;
            case '1.25': return 3.7;
            case '1.5': return 3.3;
            case '1.75': return 3.0;
            case '2.0': return 2.7;
            case '2.25': return 2.3;
            case '2.5': return 2.0;
            case '2.75': return 1.7;
            case '3.0': return 1.0;
            case '5.0': return 0.0;
            default: return null;
        }
    }
    
    private function getGPAAdvice($gpa) {
        if ($gpa >= 3.5) {
            return "🌟 **Excellent work!** Keep up the outstanding academic performance!";
        } elseif ($gpa >= 3.0) {
            return "👍 **Good job!** You're doing well academically.";
        } elseif ($gpa >= 2.5) {
            return "📈 **Keep improving!** Consider studying harder or seeking academic support.";
        } elseif ($gpa >= 2.0) {
            return "⚠️ **Academic concern.** Consider meeting with your academic advisor.";
        } else {
            return "🚨 **Need immediate attention.** Please contact your academic advisor immediately.";
        }
    }
}
?>