<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Name
            $table->integer('prep_time')->nullable(); // In minutes
            $table->binary('image')->nullable(); // Image
            $table->boolean('is_oficial')->default(value: true); // Is official
            $table->boolean('is_private')->default(value: false); // Is private
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->onUpdate('cascade'); // User ID
            $table->timestamps();
        });

        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Name
        });

        Schema::create('types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Name
        });

        Schema::create('recipe_types', function (Blueprint $table) {
            $table->foreignId('recipe_id')->constrained('recipes')->onDelete('cascade')->onUpdate('cascade'); // Recipe ID
            $table->foreignId('type_id')->constrained('types')->onDelete('cascade')->onUpdate('cascade'); // Type ID
            $table->primary(['recipe_id', 'type_id']);
        });

        Schema::create('recipe_ingredients', function (Blueprint $table) {
            $table->foreignId('recipe_id')->constrained('recipes')->onDelete('cascade')->onUpdate('cascade'); // Recipe ID
            $table->foreignId('ingredient_id')->constrained('ingredients')->onDelete('cascade')->onUpdate('cascade'); // Ingredient ID
            $table->integer('quantity'); // Quantity
            $table->primary(['recipe_id', 'ingredient_id']);
        });

        Schema::create('recipe_steps', function (Blueprint $table) {
            $table->id(); // Step ID
            $table->foreignId('recipe_id')->constrained('recipes')->onDelete('cascade')->onUpdate('cascade'); // Recipe ID
            $table->integer('step_number'); // Step number
            $table->text('description'); // Step description
        });

        Schema::create('favourites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->onUpdate('cascade'); // User ID
            $table->foreignId('recipe_id')->constrained('recipes')->onDelete('cascade')->onUpdate('cascade'); // Recipe ID
            $table->timestamps();
            $table->unique(['user_id', 'recipe_id']); // Unique combination of user_id and recipe_id
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('recipes');
        Schema::dropIfExists('favourites');
        Schema::dropIfExists('recipe_steps');
        Schema::dropIfExists('recipe_ingredients');
        Schema::dropIfExists('recipe_types');
        Schema::dropIfExists('types');
        Schema::dropIfExists('ingredients');
    }
};
