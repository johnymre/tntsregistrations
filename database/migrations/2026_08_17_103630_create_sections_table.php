<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('school_year')->nullable();
            $table->string('grade_level');
            $table->string('strand')->nullable();
            $table->string('adviser_name')->nullable();
            $table->string('room')->nullable();
            $table->integer('capacity')->default(40);
            $table->integer('enrolled_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};