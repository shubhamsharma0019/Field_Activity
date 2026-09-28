<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_activities', function (Blueprint $table) {
            $table->id();

            // Parent project
            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete();

            // Master activity type
            $table->foreignId('activity_type_id')
                ->constrained('activity_types')
                ->restrictOnDelete();

            // Project-specific activity name
            $table->string('name', 150);

            // Target, e.g. 100 posters
            $table->unsignedInteger('target_quantity')
                ->nullable();

            // Useful for canopy / long-duration work
            $table->unsignedInteger('expected_duration_minutes')
                ->nullable();

            $table->text('instructions')
                ->nullable();

            $table->date('start_date')
                ->nullable();

            $table->date('end_date')
                ->nullable();

            $table->enum('status', [
                'pending',
                'active',
                'on_hold',
                'completed',
                'cancelled',
            ])->default('pending');

            $table->timestamps();

            // Common project/activity queries
            $table->index(['project_id', 'status']);
            $table->index(['activity_type_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_activities');
    }
};