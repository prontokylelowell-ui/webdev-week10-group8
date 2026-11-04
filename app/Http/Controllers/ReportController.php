<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Note;
use App\Models\Participant;
use App\Models\Venue;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Run the seven required report queries using Eloquent relationships
     * (no hand-written JOINs) and hand the results to the Blade view.
     */
    public function index(): View
    {
        // 1. Every venue with the number of events it hosts.
        $venuesWithEventCounts = Venue::withCount('events')->orderBy('name')->get();

        // 2. All events at one chosen venue (the first venue alphabetically).
        $chosenVenue = Venue::with('events')->orderBy('name')->first();

        // 3. All participants of one chosen event, with each person's status.
        $chosenEvent = Event::with('participants')->orderBy('id')->first();

        // 4. All events one chosen participant has joined.
        $chosenParticipant = Participant::with('events')->orderBy('id')->first();

        // 5. All events that have at least one waitlisted participant.
        $eventsWithWaitlisted = Event::whereHas('participants', function ($query) {
            $query->where('status', 'waitlisted');
        })
            ->withCount(['waitlistedParticipants as waitlisted_count'])
            ->orderBy('title')
            ->get();

        // 6. All notes of one chosen event, and all notes of one chosen venue.
        $chosenEventNotes = $chosenEvent?->notes ?? collect();
        $chosenVenueNotes = $chosenVenue?->notes ?? collect();

        // 7. For one chosen note: the name of the thing it belongs to and its type.
        $chosenNote = Note::with('noteable')->orderBy('id')->first();
        $chosenNoteTarget = $chosenNote?->noteable;
        $chosenNoteTargetName = $chosenNoteTarget?->title ?? $chosenNoteTarget?->name;
        $chosenNoteTargetType = $chosenNote?->noteable_type
            ? class_basename($chosenNote->noteable_type)
            : null;

        return view('reports.index', [
            'venuesWithEventCounts' => $venuesWithEventCounts,
            'chosenVenue' => $chosenVenue,
            'chosenEvent' => $chosenEvent,
            'chosenParticipant' => $chosenParticipant,
            'eventsWithWaitlisted' => $eventsWithWaitlisted,
            'chosenEventNotes' => $chosenEventNotes,
            'chosenVenueNotes' => $chosenVenueNotes,
            'chosenNote' => $chosenNote,
            'chosenNoteTargetName' => $chosenNoteTargetName,
            'chosenNoteTargetType' => $chosenNoteTargetType,
        ]);
    }
}
