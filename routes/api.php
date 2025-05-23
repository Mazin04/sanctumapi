<?php

use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\PrivateController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FavouriteController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\TranslationController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\TypeController;

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
Route::get('email/registered', [AuthController::class, 'isEmailRegistered']);


Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'user']);
    Route::get('user/avatar', [GoogleAuthController::class, 'avatar']);

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

    // Make private or public recipe
    Route::post('/recipes/{id}/private', [PrivateController::class, 'makePrivate']);
    Route::post('/recipes/{id}/public', [PrivateController::class, 'makePublic']);

    // CRUD Recipes
    Route::post('/recipes', [RecipeController::class, 'store']);
    Route::get('/recipes/{id}', [RecipeController::class, 'show']);
    Route::put('/recipes/{id}', [RecipeController::class, 'update']);
    Route::delete('/recipes/{id}', [RecipeController::class,'destroy']);

    // CRUD Ingredients
    Route::delete('/ingredients', [IngredientController::class, 'destroyAll']);
    Route::get('/ingredients', [IngredientController::class, 'index']);
    Route::post('/ingredients', [IngredientController::class, 'store']);
    Route::put('/ingredients/{id}', [IngredientController::class, 'update']);
    Route::delete('/ingredients/{id}', [IngredientController::class, 'destroy']);

    // Get list ingredients
    Route::get('/ingredients/list', [IngredientController::class, 'listIngredients']);

    // Get types of recipes
    Route::post('/recipes/types', [TypeController::class, 'getTypes']);

    // Obtener las recetas publicas de un usuario
    Route::get('/user/{id}', [AuthController::class, 'user']);
    Route::get('/user/{id}/public-recipes', [RecipeController::class, 'getPublicRecipes']);
});