<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();

            // Unique company identifier
            $table->string('company_code', 30)->unique();

            // Company information
            $table->string('name', 150);
            $table->string('contact_person', 150)->nullable();

            // Contact information
            $table->string('mobile', 20)->nullable();
            $table->string('email', 150)->nullable();

            // Address information
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();

            // Account status
            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->timestamps();

            // Useful indexes
            $table->index('name');
            $table->index('mobile');
            $table->index('email');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};