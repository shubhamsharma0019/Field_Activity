<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_sessions', function (Blueprint $table) {
            $table->id();

            // Assignment
            $table->foreignId('assignment_id')
                ->constrained('project_assignments')
                ->cascadeOnDelete();

            // Worker
            $table->foreignId('worker_id')
                ->constrained('users')
                ->restrictOnDelete();

            // -------------------------
            // START EVIDENCE
            // -------------------------

            $table->string('start_image_path', 500);

            $table->decimal('start_latitude', 10, 7);
            $table->decimal('start_longitude', 10, 7);

            $table->decimal('start_location_accuracy', 8, 2)
                ->nullable();

            $table->dateTime('start_device_timestamp')
                ->nullable();

            // Server authoritative start time
            $table->dateTime('started_at');

            // -------------------------
            // END EVIDENCE
            // -------------------------

            $table->string('end_image_path', 500)
                ->nullable();

            $table->decimal('end_latitude', 10, 7)
                ->nullable();

            $table->decimal('end_longitude', 10, 7)
                ->nullable();

            $table->decimal('end_location_accuracy', 8, 2)
                ->nullable();

            $table->dateTime('end_device_timestamp')
                ->nullable();

            // Server authoritative completion time
            $table->dateTime('completed_at')
                ->nullable();

            // -------------------------
            // DURATION
            // -------------------------

            $table->unsignedInteger('expected_duration_minutes')
                ->nullable();

            $table->unsignedInteger('actual_duration_minutes')
                ->nullable();

            $table->text('remark')
                ->nullable();

            // -------------------------
            // STATUS
            // -------------------------

            $table->enum('status', [
                'in_progress',
                'pending_approval',
                'approved',
                'rejected',
            ])->default('in_progress');

            // -------------------------
            // ADMIN REVIEW
            // -------------------------

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('reviewed_at')
                ->nullable();

            $table->text('rejection_reason')
                ->nullable();

            $table->timestamps();

            // -------------------------
            // INDEXES
            // -------------------------

            $table->index(
                ['worker_id', 'status'],
                'session_worker_status_idx'
            );

            $table->index(
                ['assignment_id', 'status'],
                'session_assignment_status_idx'
            );

            $table->index(
                ['status', 'created_at'],
                'session_approval_queue_idx'
            );

            $table->index(
                ['worker_id', 'started_at'],
                'session_worker_history_idx'
            );

            $table->index(
                ['reviewed_by', 'reviewed_at'],
                'session_review_history_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_sessions');
    }
};