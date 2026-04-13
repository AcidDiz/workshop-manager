<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workshop_reminder_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 32);
            $table->date('dispatch_date');
            $table->timestamps();

            $table->unique(['workshop_id', 'user_id', 'kind', 'dispatch_date'], 'workshop_reminder_dispatches_unique_dispatch');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshop_reminder_dispatches');
    }
};
