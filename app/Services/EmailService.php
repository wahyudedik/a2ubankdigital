<?php

namespace App\Services;

use App\Jobs\SendEmailJob;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailService
{
    /**
     * Send email using template (dispatched to queue)
     */
    public function send(string $toEmail, string $toName, string $subject, string $templateName, array $templateData): bool
    {
        try {
            SendEmailJob::dispatch($toEmail, $toName, $subject, $templateName, $templateData);
            return true;
        } catch (\Exception $e) {
            Log::error('Email dispatch failed', ['to' => $toEmail, 'subject' => $subject, 'error' => $e->getMessage()]);
            // Fallback: send synchronously
            try {
                Mail::send("emails.{$templateName}", $templateData, function ($message) use ($toEmail, $toName, $subject) {
                    $message->to($toEmail, $toName)->subject($subject)->from(config('mail.from.address'), config('mail.from.name'));
                });
                return true;
            } catch (\Exception $e2) {
                Log::error('Email sync fallback also failed', ['error' => $e2->getMessage()]);
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
