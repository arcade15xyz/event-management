<?php

namespace App\Console\Commands;

use App\Models\Event;
use Illuminate\Support\Str;
use Illuminate\Console\Command;


class SendEventReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-event-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends notifications to all event attendees that event starts soon.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $event = Event::with('attendees.user')
            ->whereBetween('start_time', [now(), now()->addDay()])->get();

            $eventCount = $event->count();

        $eventLabel = Str::plural('event', $eventCount);

        $this->info("Found {$eventCount} {$eventLabel}.");

        $event->each(
            fn($event)=> $event->attendees->each(
                fn($attendee)=>$this->info("Notifying the user {$attendee->user->id}")));

        $this->info('Reminder notifications send successfully!');
    }
}
