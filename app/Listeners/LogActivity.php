<?php

namespace App\Listeners;

use App\Events\ActivityOccurred;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogActivity implements ShouldQueue
{
    use InteractsWithQueue;
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
        activity('audit_trail') // log name terpisah, supaya gampang difilter di menu Audit nanti
            ->performedOn($event->subject)
            ->causedBy($event->causer)
            ->withProperties($event->properties)
            ->log($event->description);
    }
}
