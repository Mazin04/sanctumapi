<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Http\Request;

class RecipeController extends Controller
{
    public function filterByIngredient(Request $request)
    {
        $ingredientIDs = $request->input('ingredients');

        if (!is_array($ingredientIDs) || empty($ingredientIDs)) {
            return response()->json(['error' => 'Debes proporcionar al menos un ingrediente.'], 400);
        }

        $recetas = Recipe::ingredient($ingredientIDs)->get();

        return response()->json($recetas);
    }

    public function getAllUsersRecipes(Request $request)
    {
        $user  = $request->user();

        $recipes = Recipe::where('is_official', true)
            ->orWhereHas('usersWhoFavourited', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->get();

        return response()->json($recipes);
    }
}
