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

    /**
     * Get all favourite recipes for the authenticated user.
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getUserFavouriteRecipes(Request $request)
    {
        $user = $request->user();
        $lang = $request->input('lang', 'es'); // idioma por defecto (en or es)
        $recipes = Recipe::whereHas('usersWhoFavourited', function ($query) use ($user) {
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

    /**
     * Get all recipes created by the authenticated user.
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getUserCreatedRecipes(Request $request)
    {
        $user = $request->user();
        $lang = $request->input('lang', 'es'); // idioma por defecto (en or es)
        $recipes = Recipe::where('creator_id', $user->id)
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

    /**
     * Get recipes by name.
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getRecipesByName(Request $request)
    {
        $lang = $request->input('lang', 'es'); // idioma por defecto
        $name = $request->input('name');

        if (!$name) {
            return response()->json(['error' => 'Debes proporcionar un nombre de receta.'], 400);
        }

        $recipes = Recipe::whereHas('translations', function ($query) use ($name, $lang) {
            $query->where('language', $lang)
                ->where('name', 'like', '%' . $name . '%');
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

        if ($recipes->isEmpty() && $lang == "es") {
            return response()->json(['error' => 'Receta no encontrada.'], 404);
        } else if ($recipes->isEmpty() && $lang == 'en' || $lang == '') {
            return response()->json(['error' => 'Recipe not found.'], 404);
        }


        return response()->json($recipes);
    }

    public function getRecipesByType(Request $request)
    {
        $user = $request->user();
        $lang = $request->input('lang', 'es'); // idioma por defecto
        $typeName = $request->input('type');

        if (!$typeName) {
            return response()->json(['error' => 'Debes proporcionar un tipo de receta.'], 400);
        }

        $recipes = Recipe::where(function ($query) use ($user) {
            $query->where('is_official', true)
                ->orWhereHas('usersWhoFavourited', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
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
            ->whereHas('types.translations', function ($query) use ($typeName, $lang) {
                $query->where('language', $lang)
                    ->where('name', 'like', '%' . $typeName . '%');
            })
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
            });;

        if ($recipes->isEmpty() && $lang == "es") {
            return response()->json(['error' => 'No se encontraron recetas con ese tipo.'], 404);
        } else if ($recipes->isEmpty() && $lang == 'en' || $lang == '') {
            return response()->json(['error' => 'No recipes found with that type.'], 404);
        }

        return response()->json($recipes);
    }
}
