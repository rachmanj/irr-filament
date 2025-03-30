<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('filament_imports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('importer');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('file_path');
            $table->string('file_name');
            $table->string('file_disk');
            $table->unsignedInteger('total_rows');
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('successful_rows')->default(0);
            $table->boolean('completed')->default(false);
            $table->timestamps();
        });

        Schema::create('filament_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('filament_imports')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->json('values');
            $table->json('validation_errors')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('filament_import_rows');
        Schema::dropIfExists('filament_imports');
    }
}; 