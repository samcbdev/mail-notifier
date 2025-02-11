<?php

namespace Samcbdev\MailNotifier\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class HandleMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $details;
    protected $fromAddress;

    /**
     * Create a new message instance.
     */
    public function __construct($details, $fromAddress)
    {
        $this->details = $details;
        $this->fromAddress = $fromAddress;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->fromAddress['email'] ?? 'samcbdev@mailnotifier.com', $this->fromAddress['name'] ?? 'Mail Notifier'),
            subject: $this->details['subject'],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mailnotifier::mailTemp',
            with: ['content' => $this->details['content']]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachmentsArr = [];
        if ($this->details['attachments']) {
            foreach ($this->details['attachments'] as $key => $value) {
                $attachmentsArr[] = Attachment::fromPath($value);
            }
        }

        return $attachmentsArr;
    }
}
