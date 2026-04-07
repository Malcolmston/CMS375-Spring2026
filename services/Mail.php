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
    private static function send($to, $from, $subject, $message, $ishtml = true): bool
    {
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

    /**
     * Sends an email by rendering a specified template with provided data.
     *
     * @param string $to The recipient's email address.
     * @param string $from The sender's email address.
     * @param string $subject The subject of the email.
     * @param string $template The name of the template file to be used for the email content.
     * @param array $data An associative array of data to be extracted and injected into the template.
     * @return bool Returns true if the email was sent successfully, or false if an error occurred, such as a missing template file.
     */
    private static function sendTemplate($to, $from, $subject, $template, $data): bool
    {
        $templatePath = __DIR__ . '/templates/' . $template;
        if (!file_exists($templatePath)) {
            error_log("Mail template not found: {$templatePath}");
            return false;
        }
        ob_start();
        extract($data, EXTR_SKIP);
        include $templatePath;
        $message = ob_get_clean();
        return self::send($to, $from, $subject, $message);
    }

    /**
     * Sends an account creation email to the specified recipient.
     *
     * @param string $fullname The full name of the account holder.
     * @param string $to The recipient's email address.
     * @param string $account_type The type of account that was created.
     * @param string $created_at The date and time when the account was created.
     * @return bool Returns true if the account creation email was sent successfully, or false if an error occurred.
     */
    public static function send_account_create($fullname, $to, $account_type, $created_at): bool
    {
        $from = MailType::DEFAULT->toString();
        $subject = "Account created";
        $template = "account_create.php";
        $data = [
            '$full_name' => $fullname,
            'email' => $to,
            'account_type' => $account_type,
            'created_at' => $created_at
        ];
        return self::sendTemplate($to, $from, $subject, $template, $data);
    }
}
