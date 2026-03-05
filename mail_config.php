<?php
// 1. Define SMTP Credentials as constants for cleaner, central management
// IMPORTANT: REPLACE THESE PLACEHOLDERS with the details from Step 1
define('SMTP_USERNAME', 'ecotrack202@gmail.com'); 
define('SMTP_PASSWORD', 'aqlsozatiphrtfge'); 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 2. Include the PHPMailer classes you downloaded
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// 3. Define the reusable function
function send_resolution_notification($recipient_email, $recipient_name, $complaint_id, $complaint_title) {
    
    // Safety check: Filter the recipient email address before attempting to send
    if (!filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
        return false; 
    }

    $mail = new PHPMailer(true);

    try {
        // SMTP Server Configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME; // The single SENDER email
        $mail->Password   = SMTP_PASSWORD; // The App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
        $mail->Port       = 465;                         

        // Sender (Always the same) & Dynamic Recipient (Changes for every user)
        $mail->setFrom(SMTP_USERNAME, 'EcoTrack Resolution Team');
        $mail->addAddress($recipient_email, $recipient_name); // This is the dynamic user's email

        // Email Content
        $mail->isHTML(true); 
        $mail->Subject = "EcoTrack Complaint Resolved: #{$complaint_id} - {$complaint_title}";
        $mail->Body    = "
            <html>
            <body>
                <h2>Dear " . htmlspecialchars($recipient_name) . ",</h2>
                <p>We are pleased to inform you that your complaint submitted to EcoTrack has been **RESOLVED** by the Municipal Administration.</p>
                <hr>
                <p><strong>Complaint ID:</strong> #{$complaint_id}</p>
                <p><strong>Title:</strong> " . htmlspecialchars($complaint_title) . "</p>
                <p><strong>Status:</strong> <span style='color: green; font-weight: bold;'>Resolved</span></p>
                <p>Thank you for using EcoTrack. We appreciate your vigilance.</p>
                <p>Sincerely,</p>
                <p>The EcoTrack Team</p>
            </body>
            </html>
        ";
        $mail->AltBody = "Complaint #{$complaint_id} ({$complaint_title}) has been RESOLVED.";

        $mail->send();
        return true; 
    } catch (Exception $e) {
        // Log the error if sending fails
        // echo "Mailer Error: {$mail->ErrorInfo}";
        return false;
    }
}
?>