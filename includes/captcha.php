<?php
// NO session_start() here!

function generateCaptcha() {
    $num1 = rand(1, 9);
    $num2 = rand(1, 9);
    $_SESSION['captcha_question'] = "$num1 + $num2";
    $_SESSION['captcha_answer'] = $num1 + $num2;
    // Debug log
    error_log("New CAPTCHA generated. Question: {$_SESSION['captcha_question']}, Answer: {$_SESSION['captcha_answer']}");
}

function verifyCaptcha($input) {
    // Debug log
    error_log("Verifying CAPTCHA - User input: " . $input);
    error_log("Stored answer: " . ($_SESSION['captcha_answer'] ?? 'not set'));
    
    if (!isset($_SESSION['captcha_answer'])) {
        error_log("CAPTCHA answer not found in session");
        return false;
    }
    
    $result = intval($input) === $_SESSION['captcha_answer'];
    error_log("CAPTCHA verification result: " . ($result ? "success" : "failure"));
    
    // Clear the CAPTCHA after verification to prevent reuse
    if ($result) {
        unset($_SESSION['captcha_answer']);
        unset($_SESSION['captcha_question']);
    }
    
    return $result;
}
