<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_documents')) {
            return;
        }

        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('document_type', 80);
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('path');
            $table->string('mime_type', 120);
            $table->string('extension', 12);
            $table->unsignedBigInteger('original_size_bytes')->default(0);
            $table->unsignedBigInteger('stored_size_bytes')->default(0);
            $table->string('checksum', 64);
            $table->timestamps();

            $table->unique(['empleado_id', 'document_type'], 'employee_document_type_unique');
            $table->index(['empleado_id', 'created_at'], 'employee_document_employee_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
    }
};
