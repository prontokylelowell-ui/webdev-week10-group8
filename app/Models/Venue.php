<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Venue extends Model
{
    protected $fillable = ['name', 'address', 'capacity'];

    /**
     * One-to-many: a venue hosts many events.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Polymorphic: a venue can have many notes.
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }
}
