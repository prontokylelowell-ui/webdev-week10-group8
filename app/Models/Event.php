<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Event extends Model
{
    protected $fillable = ['venue_id', 'title', 'description', 'starts_at'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime'];
    }

    /**
     * One-to-many (inverse): an event happens at one venue.
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * Many-to-many: an event has many participants.
     * The pivot "status" column tells us whether each person is
     * registered or waitlisted for this specific event.
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Participant::class, 'event_participant')
            ->withPivot('status')
            ->withTimestamps();
    }

    /**
     * Same many-to-many link, narrowed to the waitlisted registrations only.
     */
    public function waitlistedParticipants(): BelongsToMany
    {
        return $this->participants()->wherePivot('status', 'waitlisted');
    }

    /**
     * Polymorphic: an event can have many notes.
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }
}
