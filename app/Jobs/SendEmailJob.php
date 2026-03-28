<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public string $toEmail,
        public string $toName,
        public string $subject,
        public string $templateName,
        public array $templateData
    ) {}

    public function handle(): void
    {
        try {
            Mail::send("emails.{$this->templateName}", $this->templateData, function ($message) {
                $message->to($this->toEmail, $this->toName)
                    ->subject($this->subject)
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });
        } catch (\Exception $e) {
            Log::error('Email job failed', ['to' => $this->toEmail, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
