<?php

namespace Database\Seeders;

use App\Models\Participant;
use App\Models\Venue;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Build a complete Campus Events dataset:
     * 3 venues, 6 events, 8 participants, 12 registrations (both statuses
     * used) and 6 notes (three on events, three on venues).
     */
    public function run(): void
    {
        // ----- 3 venues ---------------------------------------------------
        $venues = collect([
            ['name' => 'Grand Auditorium', 'address' => 'Main Campus, Building A', 'capacity' => 500],
            ['name' => 'Innovation Hall', 'address' => 'Engineering Building, 2F', 'capacity' => 200],
            ['name' => 'North Open Grounds', 'address' => 'North Field', 'capacity' => 1000],
        ])->map(fn (array $venue) => Venue::create($venue));

        // ----- 6 events, created through the venue relationship -----------
        $eventsByVenue = [
            0 => [
                ['title' => 'Opening Ceremony', 'description' => 'Kick-off of Campus Events week.', 'starts_at' => '2026-11-10 08:00:00'],
                ['title' => 'Tech Talks', 'description' => 'Short talks from student developers.', 'starts_at' => '2026-11-10 13:00:00'],
            ],
            1 => [
                ['title' => 'Hackathon', 'description' => '24-hour build sprint.', 'starts_at' => '2026-11-11 09:00:00'],
                ['title' => 'Robotics Expo', 'description' => 'Robotics club showcase.', 'starts_at' => '2026-11-11 14:00:00'],
                ['title' => 'Career Fair', 'description' => 'Meet partner companies.', 'starts_at' => '2026-11-12 09:00:00'],
            ],
            2 => [
                ['title' => 'Intramurals', 'description' => 'Inter-department sports day.', 'starts_at' => '2026-11-13 07:00:00'],
            ],
        ];

        $events = collect();

        foreach ($eventsByVenue as $venueIndex => $rows) {
            $events = $events->merge($venues[$venueIndex]->events()->createMany($rows));
        }

        // ----- 8 participants ---------------------------------------------
        $participants = collect([
            ['name' => 'Kyle Pronto', 'email' => 'kyle.pronto@example.com'],
            ['name' => 'Aldred Recana', 'email' => 'aldred.recana@example.com'],
            ['name' => 'Mhyco Faulve', 'email' => 'mhyco.faulve@example.com'],
            ['name' => 'John Roxas', 'email' => 'john.roxas@example.com'],
            ['name' => 'Rafael Dayot', 'email' => 'rafael.dayot@example.com'],
            ['name' => 'Alaiza Caranto', 'email' => 'alaiza.caranto@example.com'],
            ['name' => 'Kim Delos Santos', 'email' => 'kim.delossantos@example.com'],
            ['name' => 'Nicole Bautista', 'email' => 'nicole.bautista@example.com'],
        ])->map(fn (array $participant) => Participant::create($participant));

        // ----- 12 registrations, attached through the many-to-many link ----
        // [$eventIndex => [[participantIndex, status], ...]]
        $registrations = [
            0 => [[0, 'registered'], [1, 'registered'], [2, 'waitlisted']],
            1 => [[0, 'registered'], [3, 'waitlisted']],
            2 => [[1, 'registered'], [4, 'registered'], [5, 'waitlisted']],
            3 => [[2, 'registered']],
            4 => [[6, 'registered'], [7, 'waitlisted']],
            5 => [[3, 'registered']],
        ];

        foreach ($registrations as $eventIndex => $rows) {
            $sync = collect($rows)
                ->mapWithKeys(fn (array $row) => [$participants[$row[0]]->id => ['status' => $row[1]]])
                ->all();

            $events[$eventIndex]->participants()->attach($sync);
        }

        // ----- 6 notes (3 on events, 3 on venues), created through the
        //       polymorphic relationship -----------------------------------
        $events[0]->notes()->createMany([
            ['body' => 'Doors open at 7:00 AM; ushers must be at the lobby by 6:30.'],
            ['body' => 'Reserve the front row for the guest speaker.'],
        ]);
        $events[2]->notes()->createMany([
            ['body' => 'Provide extension cords and a spare router for the Hackathon.'],
        ]);

        $venues[0]->notes()->createMany([
            ['body' => 'Air conditioning must be switched on one hour before events.'],
            ['body' => 'Stage lights are stored in the back room.'],
        ]);
        $venues[1]->notes()->createMany([
            ['body' => 'Projector bulb was replaced last month.'],
        ]);
    }
}
