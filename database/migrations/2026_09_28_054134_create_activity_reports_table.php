<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_reports', function (Blueprint $table) {
            $table->id();

            // Assignment against which issue is reported
            $table->foreignId('assignment_id')
                ->constrained('project_assignments')
                ->cascadeOnDelete();

            // Worker who reported the issue
            $table->foreignId('worker_id')
                ->constrained('users')
                ->restrictOnDelete();

            // Report category
            $table->enum('report_type', [
                'technical_issue',
                'location_issue',
                'material_issue',
                'permission_issue',
                'safety_issue',
                'other',
            ])->default('other');

            // Short report title
            $table->string('title', 150);

            // Full problem description
            $table->text('description');

            // Optional evidence
            $table->string('image_path', 500)
                ->nullable();

            // Optional location where issue occurred
            $table->decimal('latitude', 10, 7)
                ->nullable();

            $table->decimal('longitude', 10, 7)
                ->nullable();

            $table->decimal('location_accuracy', 8, 2)
                ->nullable();

            // Workflow
            $table->enum('status', [
                'open',
                'in_review',
                'resolved',
                'rejected',
            ])->default('open');

            // Admin who handled the report
            $table->foreignId('resolved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('resolution_note')
                ->nullable();

            $table->dateTime('resolved_at')
                ->nullable();

            $table->timestamps();

            // Worker report history
            $table->index(
                ['worker_id', 'created_at'],
                'activity_report_worker_idx'
            );

            // Assignment issues
            $table->index(
                ['assignment_id', 'status'],
                'activity_report_assignment_idx'
            );

            // Admin issue queue
            $table->index(
                ['status', 'created_at'],
                'activity_report_status_idx'
            );

            // Resolution history
            $table->index(
                ['resolved_by', 'resolved_at'],
                'activity_report_resolution_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_reports');
    }
};