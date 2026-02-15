<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skills', function (Blueprint $table) {
            $table->text('icon')->default('💻')->change();
        });

        Schema::table('services', function (Blueprint $table) {
            $table->text('icon')->default('💻')->change();
        });
    }

    public function down(): void
    {
        Schema::table('skills', function (Blueprint $table) {
            $table->string('icon')->default('💻')->change();
        });

        Schema::table('services', function (Blueprint $table) {
            $table->string('icon')->default('💻')->change();
        });
    }
};
