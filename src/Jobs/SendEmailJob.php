<?php

namespace Samcbdev\MailNotifier\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Samcbdev\MailNotifier\Mail\HandleMail;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $details;
    protected $fromAddress;

    /**
     * Create a new job instance.
     */
    public function __construct($details, $fromAddress)
    {
        $this->details = $details;
        $this->fromAddress = $fromAddress;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $mail = Mail::to($this->details['mail_to'])
                ->cc($this->details['cc'] ?? null)
                ->bcc($this->details['bcc'] ?? null);

            $mail->queue(new HandleMail(
                [
                    'subject' => $this->details['subject'],
                    'content' => $this->details['content'],
                    'attachments' => $this->details['attachments']
                ],
                $this->fromAddress
            ));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $error = response()->json([
                'error' => 'Mail Notifier Job Exception',
                'message' => $e->getMessage()
            ], 404);
            Log::info($error);
        }
    }
}
