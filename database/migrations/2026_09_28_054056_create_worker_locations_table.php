<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_locations', function (Blueprint $table) {
            $table->id();

            // Worker whose location is being recorded
            $table->foreignId('worker_id')
                ->constrained('users')
                ->restrictOnDelete();

            // Assignment for which tracking is active
            $table->foreignId('assignment_id')
                ->constrained('project_assignments')
                ->cascadeOnDelete();

            // GPS coordinates
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            // GPS accuracy in meters
            $table->decimal('accuracy', 8, 2)
                ->nullable();

            // Device speed
            $table->decimal('speed', 8, 2)
                ->nullable();

            // Direction: 0 - 360 degrees
            $table->decimal('heading', 6, 2)
                ->nullable();

            // Time location was recorded
            $table->dateTime('recorded_at');

            $table->timestamps();

            // Fetch route for one assignment
            $table->index(
                ['assignment_id', 'recorded_at'],
                'worker_location_assignment_time_idx'
            );

            // Worker location history
            $table->index(
                ['worker_id', 'recorded_at'],
                'worker_location_worker_time_idx'
            );

            // Worker + assignment tracking queries
            $table->index(
                ['worker_id', 'assignment_id', 'recorded_at'],
                'worker_location_tracking_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_locations');
    }
};