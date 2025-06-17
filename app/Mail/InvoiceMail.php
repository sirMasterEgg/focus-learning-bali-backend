<?php

namespace App\Mail;

use App\Models\UserDonation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    private UserDonation $userDonation;
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

    /**
     * Create a new message instance.
     */
    public function __construct(UserDonation $userDonation)
    {
        $this->userDonation = $userDonation;
        $this->greeting = 'Thank you for your donation!';
        $this->introLines = [
            'We appreciate your generous contribution to our cause.',
            'Below are the details of your donation:'
        ];
        $this->actionUrl = route('donations.show', $userDonation->id); // Example route
        $this->outroLines = [
            'This receipt may be used for tax deduction purposes.',
            'If you have any questions, please contact our support team.'
        ];
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Donation Receipt #' . $this->userDonation->human_readable_id,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invoice',
            with: [
                'userDonation' => $this->userDonation,
                'greeting' => $this->greeting,
                'introLines' => $this->introLines,
                'actionUrl' => $this->actionUrl,
                'outroLines' => $this->outroLines,
                'donationDate' => $this->userDonation->updated_at->format('j F Y'),
                'totalAmount' => number_format($this->userDonation->amount,thousands_separator: '.', decimal_separator: ','),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
