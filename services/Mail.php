<?php

namespace services;


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

class Mail
{
    /**
     * Sends an email using the specified parameters.
     *
     * @param string $to The recipient's email address.
     * @param string $from The sender's email address.
     * @param string $subject The subject of the email.
     * @param string $message The body of the email.
     * @param bool $ishtml Optional. Indicates whether the email content is HTML. Defaults to true.
     * @return bool Returns true if the email was sent successfully, or false if an error occurred.
     */
    public static function send($to, $from, $subject, $message, $ishtml = true) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'mailpit'; // Service name in compose.yml
            $mail->Port       = 1025;      // Mailpit SMTP port
            $mail->SMTPAuth   = false;      // Mailpit doesn't require auth by default

            $mail->setFrom($from);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->isHTML($ishtml);
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }
}
