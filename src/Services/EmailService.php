<?php

declare(strict_types=1);

namespace GoldenPalms\CRM\Services;

use Illuminate\Database\Capsule\Manager as DB;
use GoldenPalms\CRM\Helpers\Helper;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class EmailService
{
    /**
     * Send booking confirmation email
     */
    public static function sendBookingConfirmation($booking, $guest): bool
    {
        if (empty($guest->email)) {
            error_log("EmailService: Cannot send - guest email is empty");
            return false;
        }
        
        error_log("EmailService: Starting sendBookingConfirmation for booking #{$booking->id} to {$guest->email}");

        // Get email template or use default
        $template = DB::table('email_templates')
            ->where('template_type', 'booking_confirmation')
            ->where('is_active', 1)
            ->first();

        if ($template) {
            $subject = self::replaceVariables($template->subject, $booking, $guest);
            $body = self::replaceVariables($template->body_html, $booking, $guest);
        } else {
            // Use default template
            $subject = "Booking Confirmation - Golden Palms Beach Resort";
            $body = self::getDefaultBookingConfirmationTemplate($booking, $guest);
        }
        
        // Generate confirmation token and links
        $appUrl = $_ENV['APP_URL'] ?? 'http://localhost:8000';
        $guestEmail = $guest->email ?? '';
        $managementLink = $appUrl . '/booking.html?ref=' . urlencode($booking->booking_reference ?? '') . '&email=' . urlencode($guestEmail);
        
        $bookingStatus = $booking->status ?? 'pending';
        $actionSection = '';
        
        if ($bookingStatus === 'pending') {
            // Generate confirmation token for one-click confirmation
            $appSecret = $_ENV['APP_SECRET'] ?? 'goldenpalms_secret_key_2024';
            $bookingId = $booking->id ?? 0;
            $hash = substr(md5($bookingId . $guestEmail . $appSecret), 0, 8);
            $confirmationToken = base64_encode($bookingId . ':' . $guestEmail . ':' . $hash);
            $confirmLink = $appUrl . '/api/bookings/confirm/' . urlencode($confirmationToken);
            
            $actionSection = '
                <div style="background: linear-gradient(135deg, #f0f0f0 0%, #e8e8e8 100%); padding: 30px; margin-top: 30px; border-radius: 8px; text-align: center; border: 2px solid #667eea;">
                    <p style="margin: 0 0 20px 0; font-weight: 600; font-size: 1.1em; color: #333;">Confirm Your Booking</p>
                    <a href="' . htmlspecialchars($confirmLink) . '" style="display: inline-block; background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 1.1em; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        ✓ Confirm Booking
                    </a>
                    <p style="margin: 20px 0 10px 0; font-size: 0.9em; color: #666;">Or manage your booking:</p>
                    <a href="' . htmlspecialchars($managementLink) . '" style="display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: 600;">
                        View & Manage Booking
                    </a>
                    <p style="margin: 15px 0 0 0; font-size: 0.85em; color: #999;">Click the green button above to confirm your booking instantly, or use the link below to view details and manage your booking.</p>
                </div>';
        } else {
            // Already confirmed or other status - just show management link
            $actionSection = '
                <div style="background: linear-gradient(135deg, #f0f0f0 0%, #e8e8e8 100%); padding: 30px; margin-top: 30px; border-radius: 8px; text-align: center; border: 2px solid #667eea;">
                    <p style="margin: 0 0 15px 0; font-weight: 600; font-size: 1.1em; color: #333;">Manage Your Booking</p>
                    <a href="' . htmlspecialchars($managementLink) . '" style="display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: 600;">
                        View & Manage Booking
                    </a>
                    <p style="margin: 15px 0 0 0; font-size: 0.85em; color: #999;">Use this link to view your booking details and manage your reservation.</p>
                </div>';
        }
        
        // Append action section to email body
        $body = str_replace('</body>', $actionSection . '</body>', $body);

        // Send email
        $sent = self::sendEmail($guest->email, $guest->first_name . ' ' . $guest->last_name, $subject, $body);

        // Log communication
        if ($sent && isset($booking->id)) {
            DB::table('communications')->insert([
                'type' => 'email',
                'direction' => 'outbound',
                'guest_id' => $guest->id,
                'booking_id' => $booking->id,
                'subject' => $subject,
                'message' => $body,
                'to_email' => $guest->email,
                'status' => 'sent',
                'sent_at' => Helper::now(),
                'created_at' => Helper::now()
            ]);
        }

        return $sent;
    }

    /**
     * Replace template variables with actual values
     */
    private static function replaceVariables($text, $booking, $guest): string
    {
        $unitType = str_replace('_', ' ', $booking->unit_type ?? '');
        $unitType = ucwords($unitType);
        
        $replacements = [
            '{{guest_name}}' => $guest->first_name . ' ' . $guest->last_name,
            '{{first_name}}' => $guest->first_name,
            '{{last_name}}' => $guest->last_name,
            '{{booking_reference}}' => $booking->booking_reference ?? 'N/A',
            '{{check_in}}' => $booking->check_in ? date('F j, Y', strtotime($booking->check_in)) : 'N/A',
            '{{check_out}}' => $booking->check_out ? date('F j, Y', strtotime($booking->check_out)) : 'N/A',
            '{{unit_type}}' => $unitType,
            '{{unit_number}}' => $booking->unit_number ?? 'TBA',
            '{{number_of_guests}}' => $booking->number_of_guests ?? 2,
            '{{total_amount}}' => 'R' . number_format((float)($booking->total_amount ?? 0), 2),
            '{{balance_amount}}' => 'R' . number_format((float)($booking->balance_amount ?? 0), 2),
            '{{status}}' => ucfirst($booking->status ?? 'pending'),
            '{{resort_name}}' => 'Golden Palms Beach Resort',
            '{{resort_phone}}' => '+27 72 565 7091',
            '{{resort_email}}' => 'info@goldenpalmsbeachresort.com',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }

    /**
     * Get default booking confirmation template
     */
    private static function getDefaultBookingConfirmationTemplate($booking, $guest): string
    {
        $checkIn = $booking->check_in ? date('F j, Y', strtotime($booking->check_in)) : 'TBA';
        $checkOut = $booking->check_out ? date('F j, Y', strtotime($booking->check_out)) : 'TBA';
        $unitType = str_replace('_', ' ', $booking->unit_type ?? '');
        $unitType = ucwords($unitType);
        $nights = $booking->check_in && $booking->check_out 
            ? (int)((strtotime($booking->check_out) - strtotime($booking->check_in)) / 86400)
            : 0;

        $html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0;">🏖️ Golden Palms Beach Resort</h1>
        <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0 0;">Booking Confirmation</p>
    </div>
    
    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #e0e0e0;">
        <p>Dear ' . htmlspecialchars($guest->first_name) . ',</p>
        
        <p>Thank you for your booking! We are delighted to confirm your reservation at Golden Palms Beach Resort.</p>
        
        <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #667eea;">
            <h2 style="margin-top: 0; color: #667eea;">Booking Details</h2>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; font-weight: bold; width: 40%;">Booking Reference:</td>
                    <td style="padding: 8px 0;">' . htmlspecialchars($booking->booking_reference ?? 'N/A') . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold;">Check-in:</td>
                    <td style="padding: 8px 0;">' . htmlspecialchars($checkIn) . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold;">Check-out:</td>
                    <td style="padding: 8px 0;">' . htmlspecialchars($checkOut) . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold;">Duration:</td>
                    <td style="padding: 8px 0;">' . $nights . ' night' . ($nights != 1 ? 's' : '') . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold;">Unit Type:</td>
                    <td style="padding: 8px 0;">' . htmlspecialchars($unitType) . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold;">Number of Guests:</td>
                    <td style="padding: 8px 0;">' . htmlspecialchars($booking->number_of_guests ?? 2) . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold;">Total Amount:</td>
                    <td style="padding: 8px 0; font-size: 1.2em; color: #667eea; font-weight: bold;">R' . number_format((float)($booking->total_amount ?? 0), 2) . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold;">Status:</td>
                    <td style="padding: 8px 0;"><span style="background: #28a745; color: white; padding: 4px 12px; border-radius: 4px; font-size: 0.9em;">' . ucfirst($booking->status ?? 'Pending') . '</span></td>
                </tr>
            </table>
        </div>
        
        <p><strong>Important Information:</strong></p>
        <ul>
            <li>Please arrive after 2:00 PM on your check-in date</li>
            <li>Check-out is before 10:00 AM on your departure date</li>
            <li>Please bring a valid ID for check-in</li>
            <li>If you have any special requests, please contact us in advance</li>
        </ul>
        
        <p>If you have any questions or need to make changes to your booking, please contact us:</p>
        <div style="background: white; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <p style="margin: 5px 0;"><strong>Phone:</strong> +27 72 565 7091</p>
            <p style="margin: 5px 0;"><strong>Email:</strong> info@goldenpalmsbeachresort.com</p>
        </div>
        
        <p>We look forward to welcoming you to Golden Palms Beach Resort!</p>
        
        <p>Best regards,<br>
        <strong>The Golden Palms Beach Resort Team</strong></p>
    </div>
    
    <div style="text-align: center; margin-top: 20px; color: #999; font-size: 0.85em;">
        <p>This is an automated confirmation email. Please do not reply to this email.</p>
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * Send email using PHPMailer with SMTP support
     */
    private static function sendEmail(string $to, string $toName, string $subject, string $body): bool
    {
        $mail = null;
        try {
            $mail = new PHPMailer(true);
            
            // Enable verbose debug output in development
            $isDevelopment = ($_ENV['APP_ENV'] ?? 'production') === 'development';
            if ($isDevelopment) {
                $mail->SMTPDebug = 0; // 0 = off, 1 = client messages, 2 = client and server messages
                $mail->Debugoutput = function($str, $level) {
                    error_log("PHPMailer Debug: $str");
                };
            }
            
            // Get email configuration from environment or use defaults
            $smtpHost = $_ENV['SMTP_HOST'] ?? null;
            $smtpPort = (int)($_ENV['SMTP_PORT'] ?? 587);
            $smtpUser = $_ENV['SMTP_USER'] ?? null;
            $smtpPass = $_ENV['SMTP_PASS'] ?? null;
            $smtpSecure = $_ENV['SMTP_SECURE'] ?? 'tls'; // 'tls' or 'ssl'
            $fromEmail = $_ENV['EMAIL_FROM'] ?? 'noreply@goldenpalmsbeachresort.com';
            $fromName = $_ENV['EMAIL_FROM_NAME'] ?? 'Golden Palms Beach Resort';
            $replyTo = $_ENV['EMAIL_REPLY_TO'] ?? 'info@goldenpalmsbeachresort.com';
            
            // If SMTP is configured, use it; otherwise use PHP mail()
            if ($smtpHost && $smtpUser && $smtpPass) {
                // SMTP configuration
                $mail->isSMTP();
                $mail->Host = $smtpHost;
                $mail->SMTPAuth = true;
                $mail->Username = $smtpUser;
                $mail->Password = $smtpPass;
                $mail->SMTPSecure = $smtpSecure;
                $mail->Port = $smtpPort;
                $mail->CharSet = 'UTF-8';
                $mail->Timeout = 30; // Increase timeout for slow connections
                
                error_log("Attempting to send email via SMTP to: $to");
            } else {
                // Use PHP mail() function as fallback
                // Note: On Windows/XAMPP, PHP mail() often doesn't work without SMTP configuration
                $mail->isMail();
                error_log("Attempting to send email via PHP mail() to: $to");
                error_log("WARNING: PHP mail() may not work on Windows/XAMPP. Consider configuring SMTP in .env file.");
            }
            
            // Sender
            $mail->setFrom($fromEmail, $fromName);
            $mail->addReplyTo($replyTo, $fromName);
            
            // Recipient
            $mail->addAddress($to, $toName);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body); // Plain text version
            
            // Send email
            $result = $mail->send();
            
            if ($result) {
                error_log("Email sent successfully to: $to");
            } else {
                $errorInfo = $mail->ErrorInfo ?? 'Unknown error';
                error_log("Email send returned false for: $to");
                error_log("PHPMailer ErrorInfo: $errorInfo");
            }
            
            return $result;
            
        } catch (PHPMailerException $e) {
            $errorInfo = $mail ? $mail->ErrorInfo : $e->getMessage();
            error_log('PHPMailer Error: ' . $errorInfo);
            error_log('Exception: ' . $e->getMessage());
            
            // Fallback to PHP mail() if PHPMailer fails
            try {
                error_log("Attempting fallback to PHP mail() for: $to");
                return self::sendEmailFallback($to, $toName, $subject, $body);
            } catch (\Exception $e2) {
                error_log('Email fallback also failed: ' . $e2->getMessage());
                return false;
            }
        } catch (\Exception $e) {
            error_log('Email sending error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            // Try fallback
            try {
                error_log("Attempting fallback to PHP mail() for: $to");
                return self::sendEmailFallback($to, $toName, $subject, $body);
            } catch (\Exception $e2) {
                error_log('Email fallback also failed: ' . $e2->getMessage());
                return false;
            }
        }
    }
    
    /**
     * Fallback email sending using PHP mail() function
     */
    private static function sendEmailFallback(string $to, string $toName, string $subject, string $body): bool
    {
        $fromEmail = $_ENV['EMAIL_FROM'] ?? 'noreply@goldenpalmsbeachresort.com';
        $fromName = $_ENV['EMAIL_FROM_NAME'] ?? 'Golden Palms Beach Resort';
        
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $fromName . ' <' . $fromEmail . '>',
            'Reply-To: ' . ($_ENV['EMAIL_REPLY_TO'] ?? 'info@goldenpalmsbeachresort.com'),
            'X-Mailer: PHP/' . phpversion()
        ];

        error_log("Using PHP mail() function to send to: $to");
        $result = @mail($to, $subject, $body, implode("\r\n", $headers));
        
        if ($result) {
            error_log("PHP mail() succeeded for: $to");
        } else {
            error_log("PHP mail() failed for: $to");
            $lastError = error_get_last();
            if ($lastError) {
                error_log("Last PHP error: " . $lastError['message']);
            }
        }
        
        return $result;
    }
}

