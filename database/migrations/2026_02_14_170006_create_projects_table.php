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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->json('technologies')->default('[]');
            $table->string('image')->nullable();
            $table->string('category')->default('web');
            $table->string('link')->nullable();
            $table->string('github')->nullable();
            $table->json('videos')->default('[]');
            $table->json('images')->default('[]');
            $table->json('stats')->default('{}');
            $table->json('skills')->default('[]');
            $table->text('problem')->nullable();
            $table->json('solution')->default('[]');
            $table->json('benefits')->default('[]');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
