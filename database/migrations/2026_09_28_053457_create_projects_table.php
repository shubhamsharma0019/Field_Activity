<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            // Unique business identifier
            $table->string('project_code', 30)->unique();

            /*
             * NULL = Admin/Internal Project
             * Value = Company Project
             */
            $table->foreignId('company_id')
                ->nullable()
                ->constrained('companies')
                ->nullOnDelete();

            // Project information
            $table->string('name', 150);
            $table->text('description')->nullable();

            $table->enum('project_type', [
                'company',
                'internal',
            ]);

            // Project duration
            $table->date('start_date');
            $table->date('end_date')->nullable();

            // Project location
            $table->string('state', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('area', 150)->nullable();

            // Project lifecycle
            $table->enum('status', [
                'draft',
                'active',
                'on_hold',
                'completed',
                'cancelled',
            ])->default('draft');

            // Admin/Super Admin who created project
            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();

            // Dashboard/filter/report queries
            $table->index(['company_id', 'status']);
            $table->index(['status', 'start_date']);
            $table->index(['project_type', 'status']);
            $table->index(['state', 'city']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};