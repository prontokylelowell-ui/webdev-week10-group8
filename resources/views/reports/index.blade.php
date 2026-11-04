<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Campus Events &mdash; Relationship Report</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #f5f6f8;
            color: #1f2933;
            line-height: 1.5;
        }
        header {
            background: #12263f;
            color: #ffffff;
            padding: 24px 32px;
        }
        header h1 { margin: 0 0 4px; font-size: 22px; }
        header p { margin: 0; color: #b9c4d4; font-size: 14px; }
        main { max-width: 960px; margin: 0 auto; padding: 24px 32px 64px; }
        section {
            background: #ffffff;
            border: 1px solid #e0e4ea;
            border-radius: 8px;
            padding: 20px 24px;
            margin-bottom: 20px;
        }
        section h2 {
            margin: 0 0 4px;
            font-size: 16px;
            color: #12263f;
        }
        .query {
            margin: 0 0 16px;
            font-size: 13px;
            color: #5b6b7f;
        }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td {
            text-align: left;
            padding: 8px 10px;
            border-bottom: 1px solid #eceff3;
            vertical-align: top;
        }
        th { color: #5b6b7f; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-registered { background: #e3f3e8; color: #1c6b37; }
        .badge-waitlisted { background: #fdf0e0; color: #9a5b00; }
        .count { font-weight: 700; color: #12263f; }
        .empty { color: #8794a5; font-size: 14px; font-style: italic; }
        dl { margin: 0; }
        dt { font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: #5b6b7f; }
        dd { margin: 0 0 8px; font-size: 15px; }
    </style>
</head>
<body>
    <header>
        <h1>Campus Events &mdash; Relationship Report</h1>
        <p>Results of the seven required queries, each one labeled. Data is read through Eloquent relationships.</p>
    </header>

    <main>
        {{-- Query 1 --}}
        <section>
            <h2>1. Every venue with the number of events it hosts</h2>
            <p class="query"><code>Venue::withCount('events')-&gt;get()</code></p>
            <table>
                <thead>
                    <tr><th>Venue</th><th>Address</th><th>Events hosted</th></tr>
                </thead>
                <tbody>
                    @forelse ($venuesWithEventCounts as $venue)
                        <tr>
                            <td>{{ $venue->name }}</td>
                            <td>{{ $venue->address }}</td>
                            <td class="count">{{ $venue->events_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="empty">No venues found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        {{-- Query 2 --}}
        <section>
            <h2>2. All events at one chosen venue</h2>
            <p class="query">
                Chosen venue: <strong>{{ $chosenVenue?->name ?? '—' }}</strong>
                &middot; <code>$venue-&gt;events</code>
            </p>
            <table>
                <thead>
                    <tr><th>Event</th><th>Starts at</th></tr>
                </thead>
                <tbody>
                    @forelse ($chosenVenue?->events ?? [] as $event)
                        <tr>
                            <td>{{ $event->title }}</td>
                            <td>{{ $event->starts_at?->format('M d, Y g:i A') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="empty">This venue has no events.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        {{-- Query 3 --}}
        <section>
            <h2>3. All participants of one chosen event, with registration status</h2>
            <p class="query">
                Chosen event: <strong>{{ $chosenEvent?->title ?? '—' }}</strong>
                &middot; <code>$event-&gt;participants (withPivot('status'))</code>
            </p>
            <table>
                <thead>
                    <tr><th>Participant</th><th>Email</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse ($chosenEvent?->participants ?? [] as $participant)
                        <tr>
                            <td>{{ $participant->name }}</td>
                            <td>{{ $participant->email }}</td>
                            <td>
                                <span class="badge badge-{{ $participant->pivot->status }}">
                                    {{ $participant->pivot->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="empty">This event has no participants.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        {{-- Query 4 --}}
        <section>
            <h2>4. All events one chosen participant has joined</h2>
            <p class="query">
                Chosen participant: <strong>{{ $chosenParticipant?->name ?? '—' }}</strong>
                &middot; <code>$participant-&gt;events</code>
            </p>
            <table>
                <thead>
                    <tr><th>Event</th><th>Venue</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse ($chosenParticipant?->events ?? [] as $event)
                        <tr>
                            <td>{{ $event->title }}</td>
                            <td>{{ $event->venue?->name }}</td>
                            <td>
                                <span class="badge badge-{{ $event->pivot->status }}">
                                    {{ $event->pivot->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="empty">This participant has joined no events.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        {{-- Query 5 --}}
        <section>
            <h2>5. All events that have at least one waitlisted participant</h2>
            <p class="query"><code>Event::whereHas('participants', fn ($q) =&gt; $q-&gt;where('status', 'waitlisted'))-&gt;get()</code></p>
            <table>
                <thead>
                    <tr><th>Event</th><th>Venue</th><th>Waitlisted</th></tr>
                </thead>
                <tbody>
                    @forelse ($eventsWithWaitlisted as $event)
                        <tr>
                            <td>{{ $event->title }}</td>
                            <td>{{ $event->venue?->name }}</td>
                            <td class="count">{{ $event->waitlisted_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="empty">No event has a waitlisted participant.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        {{-- Query 6 --}}
        <section>
            <h2>6. All notes of one chosen event, and all notes of one chosen venue</h2>
            <p class="query"><code>$event-&gt;notes</code> and <code>$venue-&gt;notes</code> (polymorphic)</p>

            <h3>Notes on event: {{ $chosenEvent?->title ?? '—' }}</h3>
            <table>
                <thead>
                    <tr><th>Note</th></tr>
                </thead>
                <tbody>
                    @forelse ($chosenEventNotes as $note)
                        <tr><td>{{ $note->body }}</td></tr>
                    @empty
                        <tr><td class="empty">No notes on this event.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <h3>Notes on venue: {{ $chosenVenue?->name ?? '—' }}</h3>
            <table>
                <thead>
                    <tr><th>Note</th></tr>
                </thead>
                <tbody>
                    @forelse ($chosenVenueNotes as $note)
                        <tr><td>{{ $note->body }}</td></tr>
                    @empty
                        <tr><td class="empty">No notes on this venue.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        {{-- Query 7 --}}
        <section>
            <h2>7. A chosen note: what it belongs to, and what kind of thing it is</h2>
            <p class="query"><code>$note-&gt;noteable</code> and <code>$note-&gt;noteable_type</code></p>
            @if ($chosenNote)
                <dl>
                    <dt>Note body</dt>
                    <dd>{{ $chosenNote->body }}</dd>

                    <dt>Belongs to</dt>
                    <dd>{{ $chosenNoteTargetName ?? '—' }}</dd>

                    <dt>Kind of thing (noteable_type)</dt>
                    <dd>{{ $chosenNoteTargetType ?? '—' }}</dd>
                </dl>
            @else
                <p class="empty">No notes found.</p>
            @endif
        </section>
    </main>
</body>
</html>
