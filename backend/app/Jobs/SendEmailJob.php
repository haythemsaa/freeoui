<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;

    public function __construct(
        public User $user,
        public string $subject,
        public string $view,
        public array $data = []
    ) {}

    public function handle(): void
    {
        try {
            Mail::send($this->view, $this->data, function ($message) {
                $message->to($this->user->email, $this->user->full_name)
                    ->subject($this->subject);
            });

            Log::info('Email sent', [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
                'subject' => $this->subject,
            ]);
        } catch (\Exception $e) {
            Log::error('Email sending failed', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Email job failed', [
            'user_id' => $this->user->id,
            'subject' => $this->subject,
            'error' => $exception->getMessage(),
        ]);
    }
}
