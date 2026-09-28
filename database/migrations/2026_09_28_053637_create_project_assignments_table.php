<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_assignments', function (Blueprint $table) {
            $table->id();

            // Project
            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete();

            // Specific activity inside project
            $table->foreignId('project_activity_id')
                ->constrained('project_activities')
                ->cascadeOnDelete();

            // Assigned worker (users.role = worker)
            $table->foreignId('worker_id')
                ->constrained('users')
                ->restrictOnDelete();

            // Admin/Super Admin who assigned the work
            $table->foreignId('assigned_by')
                ->constrained('users')
                ->restrictOnDelete();

            // Worker-specific target
            $table->unsignedInteger('target_quantity')
                ->nullable();

            // Approved quantity only
            $table->unsignedInteger('completed_quantity')
                ->default(0);

            // Whether location tracking is required
            $table->boolean('tracking_required')
                ->default(false);

            $table->date('assigned_date');

            $table->enum('status', [
                'assigned',
                'in_progress',
                'pending_approval',
                'completed',
                'cancelled',
            ])->default('assigned');

            $table->timestamp('started_at')
                ->nullable();

            $table->timestamp('completed_at')
                ->nullable();

            $table->timestamps();

            // Worker dashboard
            $table->index(['worker_id', 'status']);

            // Project dashboard
            $table->index(['project_id', 'status']);

            // Activity assignment lookup
            $table->index(['project_activity_id', 'status']);

            // Tracking/history queries
            $table->index(['worker_id', 'assigned_date']);

            // Admin assignment history
            $table->index(['assigned_by', 'assigned_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_assignments');
    }
};