<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendResetPasswordCode extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    private string $code;
    private string $greeting;
    /**
     * @var array|string[]
     */
    private array $outroLines;
    /**
     * @var array|string[]
     */
    private array $introLines;
    private string $actionUrl;
    private string $actionText;

    /**
     * Create a new message instance.
     */
    public function __construct(string $code)
    {
        $this->code = $code;
        $this->greeting = 'Hello!'; // Customize as needed
        $this->introLines = ['Your one-time password is below:'];
        $this->actionUrl = 'your-action-url-here'; // Define as needed
        $this->actionText = 'Verify OTP'; // Define as needed
        $this->outroLines = ['This OTP will expire in 10 minutes.'];
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset Password Code',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: "email-reset-password",
            with: [
                'code' => $this->code,
                'greeting' => $this->greeting,
                'introLines' => $this->introLines,
                'actionUrl' => $this->actionUrl,
                'actionText' => $this->actionText,
                'outroLines' => $this->outroLines,
                'level' => 'success',
                'displayableActionUrl' => 'your-action-url-here'
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
