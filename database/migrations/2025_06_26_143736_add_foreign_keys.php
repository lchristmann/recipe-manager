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
        Schema::table('folders', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users');
        });

        Schema::table('labels', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users');
        });

        Schema::table('ratings', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('recipe_id')->constrained('users')->cascadeOnDelete();
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->foreignId('folder_id')->constrained('folders');
            $table->foreignId('user_id')->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropForeign(['folder_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn(['folder_id', 'user_id']);
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['recipe_id']);
            $table->dropColumn(['user_id', 'recipe_id']);
        });

        Schema::table('ratings', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['recipe_id']);
            $table->dropColumn(['user_id', 'recipe_id']);
        });

        Schema::table('labels', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('folders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
