<?php

namespace App\Services;

use App\Jobs\SendEmailJob;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    public function __construct(
        private LogService $logService
    ) {}

    /**
     * Send email using template (dispatched to queue)
     */
    public function send(string $toEmail, string $toName, string $subject, string $templateName, array $templateData): bool
    {
        try {
            SendEmailJob::dispatch($toEmail, $toName, $subject, $templateName, $templateData);
            return true;
        } catch (\Exception $e) {
            $this->logService->logError('Email dispatch failed', $e, [
                'to' => $toEmail,
                'subject' => $subject,
            ]);
            // Fallback: send synchronously
            try {
                Mail::send("emails.{$templateName}", $templateData, function ($message) use ($toEmail, $toName, $subject) {
                    $message->to($toEmail, $toName)->subject($subject)->from(config('mail.from.address'), config('mail.from.name'));
                });
                return true;
            } catch (\Exception $e2) {
                $this->logService->logError('Email sync fallback also failed', $e2, [
                    'to' => $toEmail,
                    'subject' => $subject,
                ]);
                return false;
            }
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
