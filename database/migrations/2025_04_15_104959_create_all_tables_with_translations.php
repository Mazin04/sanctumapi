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
        //Schema::create('recipes', function (Blueprint $table) {
        //    $table->id();
        //    $table->timestamps();
        //});
        //
        //Schema::create('recipe_translations', function (Blueprint $table) {
        //    $table->id();
        //    $table->foreignId('recipe_id')->constrained('recipes')->onDelete('cascade')->onUpdate('cascade');
        //    $table->string('language');
        //    $table->string('name')->nullable(); // Nombre de la receta en este idioma
        //    $table->text('description')->nullable(); // Descripción de la receta
        //    $table->timestamps();
        //
        //    $table->unique(['recipe_id', 'language']);
        //});
//
        //Schema::create('ingredients', function (Blueprint $table) {
        //    $table->id();
        //    $table->timestamps();
        //});
        //
        //Schema::create('ingredients_translations', function (Blueprint $table) {
        //    $table->id();
        //    $table->foreignId('ingredient_id')->constrained('ingredients')->onDelete('cascade')->onUpdate('cascade');
        //    $table->string('language'); // Código del idioma (por ejemplo, 'en', 'es')
        //    $table->string('name')->nullable(); // Nombre del ingrediente en este idioma
        //    $table->timestamps();
        //
        //    $table->unique(['ingredient_id', 'language']);
        //});
        //
        //Schema::create('recipe_ingredients', function (Blueprint $table) {
        //    $table->id();
        //    $table->foreignId('recipe_id')->constrained('recipes')->onDelete('cascade')->onUpdate('cascade');
        //    $table->foreignId('ingredient_id')->constrained('ingredients')->onDelete('cascade')->onUpdate('cascade');
        //    $table->string('quantity');
        //    $table->timestamps();
        //});
//
        //Schema::create('recipe_steps', function (Blueprint $table) {
        //    $table->id();
        //    $table->foreignId('recipe_id')->constrained('recipes')->onDelete('cascade')->onUpdate('cascade');
        //    $table->integer('step_number'); // Número del paso
        //    $table->timestamps();
        //});
//
        //Schema::create('recipe_step_translations', function (Blueprint $table) {
        //    $table->id();
        //    $table->foreignId('recipe_step_id')->constrained('recipe_steps')->onDelete('cascade')->onUpdate('cascade');
        //    $table->string('language');
        //    $table->text('step_description')->nullable(); // Descripción del paso en el idioma correspondiente
        //    $table->timestamps();
        //
        //    $table->unique(['recipe_step_id', 'language']);
        //});
       
        Schema::create('types', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('recipe_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained('recipes')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('type_id')->constrained('types')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
        });
        
        Schema::create('type_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_id')->constrained('types')->onDelete('cascade')->onUpdate('cascade');
            $table->string('language');
            $table->string('name')->nullable(); // Nombre del tipo en el idioma correspondiente
            $table->timestamps();
        
            $table->unique(['type_id', 'language']);
        });        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('all_tables_with_translations');
    }
};
