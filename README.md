# WebDev Week 10 — Campus Events (Laravel + PostgreSQL)

A small **Campus Events** data layer built with Laravel and PostgreSQL. It shows
three relationship types in Eloquent — one-to-many, many-to-many (with a pivot
that carries extra data), and polymorphic — and it demonstrates which rules
PostgreSQL itself enforces and which ones only our application code can enforce.

- **Framework:** Laravel 13
- **Database:** PostgreSQL 18
- **Language:** PHP 8.3

## What the system models

- A **venue** hosts many **events**; an event happens at one venue.
- An **event** has many **participants**; a participant joins many events.
  Each registration also stores a **status** (`registered` or `waitlisted`).
- A **note** can be attached to either an event or a venue.

## Requirements

- PHP 8.3+ with the `pdo_pgsql` extension
- Composer
- PostgreSQL 14+

## Setup

```bash
composer install

cp .env.example .env
php artisan key:generate
```

Make sure the PostgreSQL settings in `.env` point at a database you have created:

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=webdev_week10
DB_USERNAME=postgres
DB_PASSWORD=
```

Create the database, then build the schema and the sample data:

```bash
createdb -h 127.0.0.1 -U postgres webdev_week10
php artisan migrate:fresh --seed
```

The seeder builds: **3 venues, 6 events, 8 participants, 12 registrations**
(8 `registered`, 4 `waitlisted`) and **6 notes** (3 on events, 3 on venues).

## Running the report

```bash
php artisan serve
```

Then open **http://127.0.0.1:8000/reports**. The page runs the seven required
queries and labels each result.

## ERD

```
venues 1 ──────< events
  │                 │
  │                 └──< notes        (polymorphic:
  │                        │           noteable_type + noteable_id)
  └──< notes               │
                           │
events >──────< participants
      event_participant
      (pivot: status)
```

| Table | Primary key | Foreign keys |
| --- | --- | --- |
| `venues` | `id` | — |
| `events` | `id` | `venue_id` → `venues.id` |
| `participants` | `id` | — |
| `event_participant` | composite (`event_id`, `participant_id`) | `event_id` → `events.id`, `participant_id` → `participants.id` |
| `notes` | `id` | none (`noteable_type` + `noteable_id` are a polymorphic pair) |

## Relationship table

| Link | Type | Model that holds the foreign key | Methods on each model | ON DELETE | Why |
| --- | --- | --- | --- | --- | --- |
| Venue ↔ Event | One-to-many | `Event` (`venue_id`) | `Venue::events()` / `Event::venue()` | **RESTRICT** | A venue that still has events must not be deletable. |
| Event ↔ Participant | Many-to-many (via `event_participant`) | The pivot holds both keys (`event_id`, `participant_id`) | `Event::participants()` / `Participant::events()` | **CASCADE** (both) | If an event or a participant is deleted, its registrations must disappear too. |
| Note ↔ Event / Venue | Polymorphic one-to-many | `Note` (`noteable_type` + `noteable_id`) | `Event::notes()`, `Venue::notes()` / `Note::noteable()` | **none** | PostgreSQL cannot point one column at two tables, so no foreign key can exist. |

Extra rule enforced by the pivot's **composite primary key**: the same
participant cannot register for the same event twice.

## The seven report queries

All seven run through Eloquent relationships — no hand-written `JOIN`s.

| # | Requirement | Eloquent call |
| --- | --- | --- |
| 1 | Every venue with the number of events it hosts | `Venue::withCount('events')->get()` |
| 2 | All events at one chosen venue | `$venue->events` |
| 3 | All participants of one chosen event, with status | `$event->participants` (`withPivot('status')`) |
| 4 | All events one chosen participant has joined | `$participant->events` |
| 5 | All events with at least one waitlisted participant | `Event::whereHas('participants', fn ($q) => $q->where('status', 'waitlisted'))->get()` |
| 6 | All notes of a chosen event and of a chosen venue | `$event->notes` / `$venue->notes` |
| 7 | A chosen note: what it belongs to and what kind of thing it is | `$note->noteable` / `$note->noteable_type` |

## Evidence: what PostgreSQL enforces

The three statements below raise real database errors.

**1. Inserting an event with a venue that does not exist**

```sql
INSERT INTO events (venue_id, title, starts_at, created_at, updated_at)
VALUES (9999, 'Ghost Event', now(), now(), now());
```

```
ERROR:  insert or update on table "events" violates foreign key constraint "events_venue_id_foreign"
DETAIL:  Key (venue_id)=(9999) is not present in table "venues".
```

**2. Deleting a venue that still has events**

```sql
DELETE FROM venues WHERE id = 1;
```

```
ERROR:  update or delete on table "venues" violates RESTRICT setting of foreign key constraint "events_venue_id_foreign" on table "events"
DETAIL:  Key (id)=(1) is referenced from table "events".
```

**3. Registering the same participant for the same event twice**

```sql
INSERT INTO event_participant (event_id, participant_id, status, created_at, updated_at)
VALUES (1, 1, 'registered', now(), now());
```

```
ERROR:  duplicate key value violates unique constraint "event_participant_pkey"
DETAIL:  Key (event_id, participant_id)=(1, 1) already exists.
```

## Evidence: what PostgreSQL does **not** enforce

```sql
INSERT INTO notes (noteable_type, noteable_id, body, created_at, updated_at)
VALUES ('App\Models\Event', 9999, 'Orphan note pointing at a non-existent event', now(), now());
```

```
INSERT 0 1
```

The insert is **accepted**. PostgreSQL cannot reject it because a polymorphic
link stores the target's *class name* in `noteable_type` and its *id* in
`noteable_id`; a single column cannot have a foreign key that points at two
different tables, so nothing stops `noteable_id` from naming a row that does not
exist. Only application code can keep this link valid.

## Evidence: cascade cleanup

```sql
SELECT count(*) FROM event_participant WHERE event_id = 1;   -- 3
DELETE FROM events WHERE id = 1;
SELECT count(*) FROM event_participant WHERE event_id = 1;   -- 0
```

Deleting an event removes its registration rows from the pivot automatically.

## Evidence: the SQL Eloquent writes

```bash
php artisan tinker
```

```php
>>> App\Models\Event::find(1)->venue()->toSql();
=> "select * from "venues" where "venues"."id" = ?"
```

The `?` is a bound parameter; the binding Eloquent supplies is `1`. Eloquent
writes the same kind of `WHERE ... id = ?` query a hand-written lookup would,
but builds it from the relationship definition instead of a manual `JOIN`.

## Project structure

```
app/
  Http/Controllers/ReportController.php   # runs the seven queries
  Models/Venue.php                        # events(), notes()
  Models/Event.php                        # venue(), participants(), notes()
  Models/Participant.php                  # events()
  Models/Note.php                         # noteable()
database/
  migrations/                             # venues, events, participants, event_participant, notes
  seeders/DatabaseSeeder.php              # the sample dataset
resources/views/reports/index.blade.php   # the labeled report page
routes/web.php                            # GET /reports
```
