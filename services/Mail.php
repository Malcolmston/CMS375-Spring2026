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
     * Sends an email notification to the user indicating that their account has been successfully created.
     *
     * @param string $fullname The full name of the recipient.
     * @param string $to The email address of the recipient.
     * @param string $account_type The type of account that was created (e.g., basic, premium).
     * @param string $created_at The timestamp when the account was created.
     * @return bool Returns true if the email was sent successfully, otherwise false.
     */
    public static function sendAccountCreated(string $fullname, string $to, string $account_type, string $created_at): bool
    {
        $from = MailType::DEFAULT->toString();
        $subject = "Your MedHealth Account Created";
        return self::sendTemplate($to, $from, $subject, "account_created.php", [
            'full_name' => $fullname,
            'email' => $to,
            'account_type' => $account_type,
            'created_at' => $created_at
        ]);
    }

    /**
     * Sends an account locked email notification to the specified recipient.
     *
     * @param string $fullname The full name of the recipient.
     * @param string $to The email address of the recipient.
     * @param string $reason The reason why the account has been locked.
     * @return bool Returns true if the email was sent successfully, otherwise false.
     */
    public static function sendAccountLocked(string $fullname, string $to, string $reason): bool
    {
        $from = MailType::ACCOUNT->toString();
        $subject = "Your MedHealth Account Has Been Locked";
        return self::sendTemplate($to, $from, $subject, "account_locked.php", [
            'full_name' => $fullname,
            'email' => $to,
            'reason' => $reason
        ]);
    }

    /**
     * Sends an appointment confirmation email with the provided details.
     *
     * @param string $fullname The full name of the recipient.
     * @param string $to The email address of the recipient.
     * @param string $date The date of the appointment.
     * @param string $time The time of the appointment.
     * @param string $provider The name of the service provider.
     * @param string $location The location of the appointment.
     * @return bool Returns true if the email was sent successfully, false otherwise.
     */
    public static function sendAppointmentConfirmed(string $fullname, string $to, string $date, string $time, string $provider, string $location): bool
    {
        $from = MailType::SCHEDULE->toString();
        $subject = "Appointment Confirmed";
        return self::sendTemplate($to, $from, $subject, "appointment_confirmed.php", [
            'full_name' => $fullname,
            'email' => $to,
            'date' => $date,
            'time' => $time,
            'provider' => $provider,
            'location' => $location
        ]);
    }

    /**
     * Sends an email notification informing the recipient that an appointment has been cancelled.
     *
     * @param string $fullname The full name of the recipient.
     * @param string $to The email address of the recipient.
     * @param string $date The date of the cancelled appointment.
     * @param string $time The time of the cancelled appointment.
     * @param string $provider The name of the service provider related to the appointment.
     * @return bool Returns true on success, false on failure.
     */
    public static function sendAppointmentCancelled(string $fullname, string $to, string $date, string $time, string $provider): bool
    {
        $from = MailType::SCHEDULE->toString();
        $subject = "Appointment Cancelled";
        return self::sendTemplate($to, $from, $subject, "appointment_cancelled.php", [
            'full_name' => $fullname,
            'email' => $to,
            'date' => $date,
            'time' => $time,
            'provider' => $provider
        ]);
    }

    /**
     * Sends an appointment reminder email to a specified recipient.
     *
     * @param string $fullname The full name of the recipient.
     * @param string $to The email address of the recipient.
     * @param string $date The date of the appointment.
     * @param string $time The time of the appointment.
     * @param string $provider The name of the appointment provider.
     * @param string $location The location of the appointment.
     * @return bool Returns true if the email was successfully sent, false otherwise.
     */
    public static function sendAppointmentReminder(string $fullname, string $to, string $date, string $time, string $provider, string $location): bool
    {
        $from = MailType::SCHEDULE->toString();
        $subject = "Appointment Reminder";
        return self::sendTemplate($to, $from, $subject, "appointment_reminder.php", [
            'full_name' => $fullname,
            'email' => $to,
            'date' => $date,
            'time' => $time,
            'provider' => $provider,
            'location' => $location
        ]);
    }

    /**
     * Sends a billing invoice to the specified recipient.
     *
     * @param string $fullname The full name of the recipient.
     * @param string $to The email address of the recipient.
     * @param string $invoice_number The unique identifier for the invoice.
     * @param float $amount The total amount of the invoice.
     * @param string $due_date The due date for the payment of the invoice.
     * @param string $description A description or details about the invoice.
     * @return bool Returns true if the invoice was sent successfully, otherwise false.
     */
    public static function sendBillingInvoice(string $fullname, string $to, string $invoice_number, float $amount, string $due_date, string $description): bool
    {
        $from = MailType::BILLING->toString();
        $subject = "New Invoice - #{$invoice_number}";
        return self::sendTemplate($to, $from, $subject, "billing_invoice.php", [
            'full_name' => $fullname,
            'email' => $to,
            'invoice_number' => $invoice_number,
            'amount' => $amount,
            'due_date' => $due_date,
            'description' => $description
        ]);
    }

    /**
     * Sends lab results notification email to the specified recipient.
     *
     * @param string $fullname The full name of the recipient.
     * @param string $to The recipient's email address.
     * @param string $lab_name The name of the lab providing the results.
     * @param string $test_name The name of the test performed.
     * @param string $result_date The date when the test results were made available.
     * @return bool Returns true if the email was sent successfully, false otherwise.
     */
    public static function sendLabResults(string $fullname, string $to, string $lab_name, string $test_name, string $result_date): bool
    {
        $from = MailType::LAB->toString();
        $subject = "Lab Results Available";
        return self::sendTemplate($to, $from, $subject, "lab_results.php", [
            'full_name' => $fullname,
            'email' => $to,
            'lab_name' => $lab_name,
            'test_name' => $test_name,
            'result_date' => $result_date
        ]);
    }

    /**
     * Sends a login alert email to the specified recipient.
     *
     * @param string $fullname The full name of the recipient.
     * @param string $to The email address of the recipient.
     * @param string $device The device used for the login.
     * @param string $location The location from which the login occurred.
     * @param string $time The time of the login.
     * @return bool Returns true if the email was successfully sent, false otherwise.
     */
    public static function sendLoginAlert(string $fullname, string $to, string $device, string $location, string $time): bool
    {
        $from = MailType::ACCOUNT->toString();
        $subject = "New Login Detected";
        return self::sendTemplate($to, $from, $subject, "login_alert.php", [
            'full_name' => $fullname,
            'email' => $to,
            'device' => $device,
            'location' => $location,
            'time' => $time
        ]);
    }

    /**
     * Sends a password reset email to the specified recipient with a reset link and expiration time.
     *
     * @param string $fullname The full name of the recipient.
     * @param string $to The email address of the recipient.
     * @param string $reset_link The URL link for resetting the password.
     * @param int $expires_in_minutes The expiration time of the reset link in minutes.
     * @return bool Returns true on successful email sending, otherwise false.
     */
    public static function sendPasswordReset(string $fullname, string $to, string $reset_link, int $expires_in_minutes): bool
    {
        $from = MailType::ACCOUNT->toString();
        $subject = "Reset Your Password";
        return self::sendTemplate($to, $from, $subject, "password_reset.php", [
            'full_name' => $fullname,
            'email' => $to,
            'reset_link' => $reset_link,
            'expires_in_minutes' => $expires_in_minutes
        ]);
    }

    /**
     * Sends an email notification indicating that a prescription is ready for pickup.
     *
     * @param string $fullname The full name of the recipient.
     * @param string $to The email address of the recipient.
     * @param string $medicine_name The name of the prescribed medicine.
     * @param string $pharmacy_name The name of the pharmacy where the prescription is ready.
     * @param string $pickup_time The scheduled pickup time for the prescription.
     * @return bool Returns true if the email was sent successfully, otherwise false.
     */
    public static function sendPrescriptionReady(string $fullname, string $to, string $medicine_name, string $pharmacy_name, string $pickup_time): bool
    {
        $from = MailType::PHARMACY->toString();
        $subject = "Prescription Ready for Pickup";
        return self::sendTemplate($to, $from, $subject, "prescription_ready.php", [
            'full_name' => $fullname,
            'email' => $to,
            'medicine_name' => $medicine_name,
            'pharmacy_name' => $pharmacy_name,
            'pickup_time' => $pickup_time
        ]);
    }

    /**
     * Sends an email to a staff member with their Staff ID details.
     *
     * @param string $fullname The full name of the staff member.
     * @param string $to The email address of the staff member.
     * @param string $staff_id The staff ID to be sent to the staff member.
     * @param string $role The role of the staff member.
     * @param string $institution The institution associated with the staff member.
     *
     * @return bool                 True if the email is sent successfully, false otherwise.
     */
    public static function sendStaffId(string $fullname, string $to, string $staff_id, string $role, string $institution): bool
    {
        $from = MailType::DEFAULT->toString();
        $subject = "Your Staff ID - MedHealth";
        return self::sendTemplate($to, $from, $subject, "staff_id.php", [
            'full_name' => $fullname,
            'email' => $to,
            'staff_id' => $staff_id,
            'role' => $role,
            'institution' => $institution
        ]);
    }

    /**
     * Sends a verification email to the specified recipient.
     *
     * @param string $fullname The full name of the recipient.
     * @param string $to The email address of the recipient.
     * @param string $verify_link The verification link to be included in the email.
     * @return bool Returns true if the email is sent successfully, otherwise false.
     */
    public static function sendVerifyEmail(string $fullname, string $to, string $verify_link): bool
    {
        $from = MailType::DEFAULT->toString();
        $subject = "Verify Your Email Address";
        return self::sendTemplate($to, $from, $subject, "verify_email.php", [
            'full_name' => $fullname,
            'email' => $to,
            'verify_link' => $verify_link
        ]);
    }

    /**
     * Sends a welcome email to a new user.
     *
     * @param string $fullname The full name of the recipient.
     * @param string $to The email address of the recipient.
     *
     * @return bool Returns true if the email was sent successfully, false otherwise.
     */
    public static function sendWelcome(string $fullname, string $to): bool
    {
        $from = MailType::DEFAULT->toString();
        $subject = "Welcome to MedHealth";
        return self::sendTemplate($to, $from, $subject, "welcome.php", [
            'full_name' => $fullname,
            'email' => $to
        ]);
    }

    /**
     * Sends a notification email to the specified recipient.
     *
     * @param string $fullname The full name of the recipient.
     * @param string $to The email address of the recipient.
     * @param string $title The title or subject of the notification.
     * @param string $message The message content of the notification.
     * @return bool Returns true if the notification email was sent successfully, false otherwise.
     */
    public static function sendNotification(string $fullname, string $to, string $title, string $message): bool
    {
        $from = MailType::DEFAULT->toString();
        $subject = $title;
        return self::sendTemplate($to, $from, $subject, "notification.php", [
            'full_name' => $fullname,
            'email' => $to,
            'title' => $title,
            'message' => $message
        ]);
    }

    /**
     * Sends an email notification indicating that a document has been uploaded.
     *
     * @param string $fullname The full name of the recipient.
     * @param string $to The email address of the recipient.
     * @param string $document_name The name of the uploaded document.
     * @param string $uploaded_by The name of the person who uploaded the document.
     * @return bool Returns true if the email was sent successfully, false otherwise.
     */
    public static function sendDocumentUploaded(string $fullname, string $to, string $document_name, string $uploaded_by): bool
    {
        $from = MailType::DEFAULT->toString();
        $subject = "Document Uploaded";
        return self::sendTemplate($to, $from, $subject, "document_uploaded.php", [
            'full_name' => $fullname,
            'email' => $to,
            'document_name' => $document_name,
            'uploaded_by' => $uploaded_by
        ]);
    }
}
