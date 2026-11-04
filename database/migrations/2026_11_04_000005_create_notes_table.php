<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A note belongs to either an event or a venue.
     *
     * morphs() creates noteable_type (the model class) and noteable_id
     * (the row id) plus an index. There is deliberately NO foreign key
     * here: PostgreSQL cannot point one column at two different tables,
     * so this rule can only be enforced by our application code.
     */
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->morphs('noteable');
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
