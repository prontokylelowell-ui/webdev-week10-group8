<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Note extends Model
{
    protected $fillable = ['body'];

    /**
     * Polymorphic inverse: a note belongs to either an event or a venue.
     *
     * The target is resolved from the noteable_type column, so this link
     * is only checked by our code, never by a database foreign key.
     */
    public function noteable(): MorphTo
    {
        return $this->morphTo();
    }
}
