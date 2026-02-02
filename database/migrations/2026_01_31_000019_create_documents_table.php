<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('branch_id')->nullable();
            $table->uuid('uploaded_by');

            $table->string('documentable_type');
            $table->uuid('documentable_id');

            $table->string('title');
            $table->string('document_type');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type');
            $table->integer('file_size');

            $table->date('expiry_date')->nullable();
            $table->boolean('is_archived')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->foreign('uploaded_by')->references('id')->on('users');

            $table->index(['documentable_type', 'documentable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
