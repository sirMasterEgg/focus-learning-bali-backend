<?php

namespace App\Listeners;

use App\Loggable;
use Illuminate\Mail\Events\MessageSending;

class MailSendingListener
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
    public function handle(MessageSending $event): void
    {
        $this->logInfo('Mail message is being sent', [
            'to' => $event->message->getTo(),
            'subject' => $event->message->getSubject(),
            'data' => $event->data,
        ]);
    }
}
