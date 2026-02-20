<?php
/**
 * Email Configuration for NAIGO
 * Supports both Mailjet and Gmail SMTP
 */

// Configuration logic
function getEmailConfig()
{
    return [
        'mailjet' => [
            'api_key' => 'e324fa9aab609ddbf62952a7bc5e822e',
            'api_secret' => '56f145204ff4cd591e9838987bb037a9',
            'sender_email' => 'carriemaejm.naig@csucc.edu.ph',
            'sender_name' => 'NAIGO'
        ]
    ];
}

/**
 * Send OTP using available email method (Mailjet preferred)
 * @param string $to Recipient email
 * @param string $otp OTP code
 * @return bool Success status
 */
function sendOTPEmail($to, $otp)
{
    $config = getEmailConfig();

    // 1. Try Mailjet (Primary)
    if (sendViaMailjet($to, $otp, $config['mailjet'])) {
        return true;
    }

    error_log("Mailjet failed for OTP to $to");
    return false;
}

/**
 * Send email via Mailjet API
 */
function sendViaMailjet($to, $otp, $config)
{
    $subject = 'NAIGO - Password Reset Code';
    $messageText = "Hello,\n\nYou requested a password reset for your NAIGO account.\nYour verification code is: {$otp}\nThis code will expire in 15 minutes.\n\nBest regards,\nNAIGO Team";

    // HTML Template
    $messageHtml = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px;'>
        <div style='text-align: center; margin-bottom: 20px; background-color: #004d40; padding: 20px; border-radius: 5px 5px 0 0;'>
            <h1 style='color: #d4af37; margin: 0; font-size: 28px;'>NAIGO</h1>
            <p style='color: #fff; margin-top: 5px; opacity: 0.9; font-size: 14px;'>Online Restaurant Reservation</p>
        </div>
        <div style='background-color: #f9f9f9; padding: 20px; border-radius: 0 0 5px 5px; border: 1px solid #eee; border-top: none;'>
            <h2 style='color: #004d40; margin-top: 0;'>Password Reset</h2>
            <p>Hello,</p>
            <p>You requested a password reset for your NAIGO account.</p>
            <div style='background-color: #ffffff; border: 2px solid #d4af37; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 8px; color: #004d40; margin: 20px 0; border-radius: 5px;'>
                {$otp}
            </div>
            <p>This code will expire in 15 minutes.</p>
            <p style='font-size: 12px; color: #888;'>If you didn't request this, please ignore this email.</p>
        </div>
        <div style='text-align: center; margin-top: 20px; font-size: 12px; color: #999;'>
            <p>&copy; " . date('Y') . " NAIGO. All rights reserved.</p>
        </div>
    </div>
    ";

    $data = [
        'Messages' => [
            [
                'From' => [
                    'Email' => $config['sender_email'],
                    'Name' => $config['sender_name']
                ],
                'To' => [
                    [
                        'Email' => $to
                    ]
                ],
                'Subject' => $subject,
                'TextPart' => $messageText,
                'HTMLPart' => $messageHtml
            ]
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.mailjet.com/v3.1/send');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // 10 seconds connection timeout
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 seconds total timeout
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($config['api_key'] . ':' . $config['api_secret'])
    ]);

    // For local development on XAMPP (Windows), disable SSL verification to avoid certificate errors
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    $curl_errno = curl_errno($ch);
    curl_close($ch);

    if ($curl_errno) {
        error_log("Mailjet cURL Error ($curl_errno): $curl_error");
        return false; // Fail immediately if connection issue, or fall through to bad http code handling
    }

    if ($http_code === 200 || $http_code === 201) {
        return true;
    }
    else {
        error_log("Mailjet API Error: HTTP $http_code. Response: $response. Curl Error: $curl_error");
        return false;
    }
}
