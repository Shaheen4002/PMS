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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text("description");
            $table->enum("status",["pending","in_progress","complete"]);
            $table->enum("priority",["low","medium","high"]);
            $table->foreignId("project_id")->constrained("projects")->onDelete('cascade');
            $table->foreignId("creator_id")->constrained("users")->onDelete('cascade');
            $table->foreignId("user_id")->constrained("users")->onDelete('cascade');
            $table->text("info");
            $table->integer("progress");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
