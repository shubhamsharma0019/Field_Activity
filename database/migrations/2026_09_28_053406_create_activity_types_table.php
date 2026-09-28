<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_types', function (Blueprint $table) {
            $table->id();

            $table->string('name', 100)->unique();

            $table->enum('activity_mode', [
                'single_submission',
                'start_end',
                'continuous_tracking',
            ]);

            $table->boolean('tracking_required')
                ->default(false);

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->timestamps();

            // Common admin/filter query
            $table->index(['status', 'activity_mode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_types');
    }
};