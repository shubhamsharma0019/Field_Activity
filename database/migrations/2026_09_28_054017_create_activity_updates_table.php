<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_updates', function (Blueprint $table) {
            $table->id();

            // Running activity session
            $table->foreignId('session_id')
                ->constrained('activity_sessions')
                ->cascadeOnDelete();

            // Worker who submitted the update
            $table->foreignId('worker_id')
                ->constrained('users')
                ->restrictOnDelete();

            // Evidence image
            $table->string('image_path', 500);

            // GPS
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            // Optional GPS accuracy
            $table->decimal('location_accuracy', 8, 2)
                ->nullable();

            // Optional worker remark
            $table->text('remark')
                ->nullable();

            // Device time - audit/reference
            $table->dateTime('device_timestamp')
                ->nullable();

            // Backend authoritative time
            $table->dateTime('server_timestamp');

            $table->timestamps();

            // Session timeline
            $table->index(
                ['session_id', 'server_timestamp'],
                'activity_update_session_time_idx'
            );

            // Worker update history
            $table->index(
                ['worker_id', 'server_timestamp'],
                'activity_update_worker_time_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_updates');
    }
};