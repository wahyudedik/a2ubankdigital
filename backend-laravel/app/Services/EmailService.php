<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;

class EmailService
{
    /**
     * Send email using template
     *
     * @param string $toEmail
     * @param string $toName
     * @param string $subject
     * @param string $templateName
     * @param array $templateData
     * @return bool
     */
    public function send(string $toEmail, string $toName, string $subject, string $templateName, array $templateData): bool
    {
        try {
            Mail::send("emails.{$templateName}", $templateData, function ($message) use ($toEmail, $toName, $subject) {
                $message->to($toEmail, $toName)
                    ->subject($subject)
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Email sending failed', [
                'to' => $toEmail,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send OTP email
     *
     * @param string $email
     * @param string $name
     * @param string $otpCode
     * @return bool
     */
    public function sendOtp(string $email, string $name, string $otpCode): bool
    {
        return $this->send(
            $email,
            $name,
            'Kode Verifikasi OTP',
            'otp',
            [
                'full_name' => $name,
                'otp_code' => $otpCode,
                'preheader' => 'Kode verifikasi pendaftaran Anda.'
            ]
        );
    }

    /**
     * Send password reset email
     *
     * @param string $email
     * @param string $name
     * @param string $otpCode
     * @return bool
     */
    public function sendPasswordReset(string $email, string $name, string $otpCode): bool
    {
        return $this->send(
            $email,
            $name,
            'Reset Password',
            'password_reset',
            [
                'full_name' => $name,
                'otp_code' => $otpCode,
                'preheader' => 'Kode reset password Anda.'
            ]
        );
    }

    /**
     * Send welcome email
     *
     * @param string $email
     * @param string $name
     * @return bool
     */
    public function sendWelcome(string $email, string $name): bool
    {
        return $this->send(
            $email,
            $name,
            'Selamat Datang',
            'welcome',
            [
                'full_name' => $name,
                'preheader' => 'Selamat datang di platform kami.'
            ]
        );
    }
}
