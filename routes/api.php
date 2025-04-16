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
Route::get('logout', [AuthController::class, 'logout']);
Route::post('login', [AuthController::class, 'login']);
Route::get('translations/{language}', [TranslationController::class, 'getTranslations']);


Route::middleware('auth:sanctum')->group(function () {
    Route::get('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'user']);

    Route::post('/recipes/filter-by-ingredient', [RecipeController::class, 'filterByIngredient']);
    Route::post('/recipes/byName', [RecipeController::class, 'getRecipesByName']);
    Route::post('/recipes/byType', [RecipeController::class, 'getRecipesByType']);

    Route::post('/user/allRecipes', [RecipeController::class, 'getAllUsersRecipes']);
    Route::post('/user/favourites', [RecipeController::class, 'getUserFavouriteRecipes']);
    Route::post('/user/yourRecipes', [RecipeController::class, 'getUserCreatedRecipes']);
    Route::delete('/recipes/{id}/favourite', [FavouriteController::class, 'removeFromFavourites']);
    Route::post('/recipes/{id}/favourite', [FavouriteController::class, 'addToFavourites']);
});