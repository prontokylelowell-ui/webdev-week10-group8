<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The registration (pivot) table for the many-to-many link between
     * events and participants.
     *
     * - Composite primary key (event_id, participant_id) stops the same
     *   participant from registering for the same event twice.
     * - ON DELETE CASCADE removes registrations when an event or a
     *   participant is deleted.
     * - The extra "status" column carries data that belongs to the link itself.
     */
    public function up(): void
    {
        Schema::create('event_participant', function (Blueprint $table) {
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained('participants')->cascadeOnDelete();
            $table->string('status')->default('registered');
            $table->timestamps();

            $table->primary(['event_id', 'participant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_participant');
    }
};
