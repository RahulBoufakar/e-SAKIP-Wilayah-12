<?php

namespace App\Listeners;

use App\Events\ActivityOccurred;
use App\Notifications\ActivityNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendInAppNotification implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(ActivityOccurred $event): void
    {
        $recipients = collect($event->recipients)->filter();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send(
            $recipients,
            new ActivityNotification($event->subject, $event->description, $event->causer, $event->url)
        );
    }
}
