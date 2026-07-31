<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipt_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('empleado_id')->nullable()->constrained('empleados')->nullOnDelete();
            $table->foreignId('nomina_id')->nullable()->constrained('nominas')->nullOnDelete();
            $table->string('type', 40);
            $table->date('period_start');
            $table->date('period_end');
            $table->string('file_name')->nullable();
            $table->unsignedInteger('receipt_count')->default(1);
            $table->string('checksum', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['empleado_id', 'period_end'], 'receipt_employee_period_idx');
            $table->index(['type', 'created_at'], 'receipt_type_created_idx');
        });

        Schema::create('payment_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('source_name');
            $table->string('checksum', 64);
            $table->string('status', 30)->default('completed');
            $table->unsignedInteger('matched_count')->default(0);
            $table->unsignedInteger('difference_count')->default(0);
            $table->unsignedInteger('unmatched_count')->default(0);
            $table->json('results')->nullable();
            $table->timestamps();

            $table->unique(['checksum', 'period_start', 'period_end'], 'reconciliation_file_period_unique');
            $table->index(['period_end', 'created_at'], 'reconciliation_period_idx');
        });

        Schema::create('annual_archives', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->string('status', 30)->default('verified');
            $table->json('summary');
            $table->string('checksum', 64);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annual_archives');
        Schema::dropIfExists('payment_reconciliations');
        Schema::dropIfExists('receipt_generations');
    }
};
