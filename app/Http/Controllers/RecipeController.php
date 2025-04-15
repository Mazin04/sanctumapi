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

    /**
     * Get all recipes for the authenticated user.
     * This includes official recipes and those favorited by the user.
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getAllUsersRecipes(Request $request)
    {
        $user = $request->user();
        $lang = $request->input('lang', 'es'); // idioma por defecto (en or es)
    
        $recipes = Recipe::where('is_official', true)
            ->orWhereHas('usersWhoFavourited', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with([
                'translations' => function ($query) use ($lang) {
                    $query->where('language', $lang);
                },
                'recipeSteps',
                'types.translations' => function ($query) use ($lang) {
                    $query->where('language', $lang);
                }
            ])
            ->get()
            ->map(function ($recipe) {
                return [
                    'id' => $recipe->id,
                    'name' => $recipe->translations->first()->name ?? 'Sin traducción',
                    'description' => $recipe->translations->first()->description ?? null,
                    'image' => asset($recipe->image_path),
                    'is_official' => $recipe->is_official,
                    'is_private' => $recipe->is_private,
                    'steps_count' => $recipe->recipeSteps->count(),
                    'types' => $recipe->types->map(fn($t) => $t->translations->first()->name ?? 'Sin traducción'),
                ];
            });
    
        return response()->json($recipes);
    }
    
}
