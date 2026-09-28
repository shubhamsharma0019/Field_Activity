<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->string('mobile', 20)
                ->nullable()
                ->unique()
                ->after('name');

            $table->enum('role', [
                'super_admin',
                'admin',
                'company',
                'worker',
            ])->default('worker')
              ->after('password');

            $table->foreignId('company_id')
                ->nullable()
                ->after('role')
                ->constrained('companies')
                ->nullOnDelete();

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active')
              ->after('company_id');

            $table->index(['role', 'status']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->dropIndex(['role', 'status']);
            $table->dropIndex(['company_id', 'status']);

            $table->dropForeign(['company_id']);

            $table->dropColumn([
                'mobile',
                'role',
                'company_id',
                'status',
            ]);
        });
    }
};