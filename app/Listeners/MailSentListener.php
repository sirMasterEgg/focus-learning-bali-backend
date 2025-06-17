<?php

namespace App\Listeners;

use App\Loggable;
use Illuminate\Mail\Events\MessageSent;

class MailSentListener
{
    use Loggable;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(MessageSent $event): void
    {
        $this->logInfo('Email sent', [
            'to' => $event->message->getTo(),
            'subject' => $event->message->getSubject(),
            'data' => $event->data,
        ]);
    }
}
