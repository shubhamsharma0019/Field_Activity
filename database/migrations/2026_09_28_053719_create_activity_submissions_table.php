<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_submissions', function (Blueprint $table) {
            $table->id();

            // Assignment against which evidence is submitted
            $table->foreignId('assignment_id')
                ->constrained('project_assignments')
                ->cascadeOnDelete();

            // Worker who submitted evidence
            $table->foreignId('worker_id')
                ->constrained('users')
                ->restrictOnDelete();

            // Sequential evidence number within assignment
            $table->unsignedInteger('submission_no');

            // Stored image path
            $table->string('image_path', 500);

            /*
             * GPS coordinates
             * DECIMAL avoids floating-point inaccuracies.
             */
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            // GPS accuracy in meters
            $table->decimal('location_accuracy', 8, 2)
                ->nullable();

            // Timestamp reported by worker device
            $table->timestamp('device_timestamp')
                ->nullable();

            // Authoritative backend timestamp
            $table->timestamp('server_timestamp')
                ->useCurrent();

            $table->text('remark')
                ->nullable();

            $table->enum('approval_status', [
                'pending',
                'approved',
                'rejected',
            ])->default('pending');

            // Admin/Super Admin who reviewed evidence
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')
                ->nullable();

            $table->text('rejection_reason')
                ->nullable();

            $table->timestamps();

            /*
             * Prevent duplicate submission numbers
             * inside the same assignment.
             */
            $table->unique(
                ['assignment_id', 'submission_no'],
                'assignment_submission_unique'
            );

            // Worker history
            $table->index(
                ['worker_id', 'created_at'],
                'submission_worker_history_idx'
            );

            // Admin pending approval queue
            $table->index(
                ['approval_status', 'created_at'],
                'submission_approval_queue_idx'
            );

            // Assignment evidence/progress
            $table->index(
                ['assignment_id', 'approval_status'],
                'submission_assignment_status_idx'
            );

            // Review history
            $table->index(
                ['reviewed_by', 'reviewed_at'],
                'submission_review_history_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_submissions');
    }
};