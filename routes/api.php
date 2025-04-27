<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FavouriteController;
use App\Http\Controllers\TranslationController;
use App\Http\Controllers\RecipeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::get('translations/{language}', [TranslationController::class, 'getTranslations']);


Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'user']);

    // Filters
    Route::post('/recipes/filter-by-ingredient', [RecipeController::class, 'filterByIngredient']);
    Route::post('/recipes/byName', [RecipeController::class, 'getRecipesByName']);
    Route::post('/recipes/byType', [RecipeController::class, 'getRecipesByType']);
    Route::post('/recipes/available', [RecipeController::class, 'recipesAvailableForUser']);

    // User Recipes
    Route::post('/user/allRecipes', [RecipeController::class, 'getAllUsersRecipes']);
    Route::post('/user/yourRecipes', [RecipeController::class, 'getUserCreatedRecipes']);

    // Favourites
    Route::post('/user/favourites', [RecipeController::class, 'getUserFavouriteRecipes']);
    Route::delete('/recipes/{id}/favourite', [FavouriteController::class, 'removeFromFavourites']);
    Route::post('/recipes/{id}/favourite', [FavouriteController::class, 'addToFavourites']);

    // CRUD Recipes
    Route::post('/recipes', [RecipeController::class, 'store']);
    Route::get('/recipes/{id}', [RecipeController::class, 'show']);
    Route::put('/recipes/{id}', [RecipeController::class, 'update']);
    Route::delete('/recipes/{id}', [RecipeController::class,'destroy']);

    // Ideas
    // Obtener las recetas publicas de un usuario
    // Route::get('/user/{id}/public-recipes', [RecipeController::class, 'getPublicRecipesByUserId']);
});