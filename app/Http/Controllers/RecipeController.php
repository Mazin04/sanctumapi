<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Http\Request;

class RecipeController extends Controller
{
    /**
     * Filter recipes by ingredients.
     * This method retrieves recipes that contain at least one of the specified ingredients.
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function filterByIngredient(Request $request)
    {
        $user = $request->user();
        $lang = $request->input('lang', 'es'); // idioma por defecto (en or es)
        $ingredientIDs = $request->input('ingredients');

        if (!is_array($ingredientIDs) || empty($ingredientIDs)) {
            $message = $lang === 'es' 
                ? 'Debes proporcionar al menos un ingrediente.'
                : 'You must provide at least one ingredient.';
    
            return response()->json(['error' => $message], 400);
        }

        $recipes = Recipe::whereHas('ingredients', function ($query) use ($ingredientIDs) {
            $query->whereIn('ingredient_id', $ingredientIDs);
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

        if ($recipes->isEmpty()) {
            $message = $lang === 'es'
                ? 'No se encontraron recetas con esos ingredientes.'
                : 'No recipes found with those ingredients.';
    
            return response()->json(['error' => $message], 404);
        }

        return response()->json($recipes);
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
        
        if ($recipes->isEmpty()) {
            $message = $lang === 'es'
                ? 'No se encontraron recetas.'
                : 'No recipes found.';
    
            return response()->json(['error' => $message], 404);
        }

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

        if ($recipes->isEmpty()) {
            $message = $lang === 'es'
                ? 'No se encontraron recetas favoritas.'
                : 'No favourite recipes found.';
    
            return response()->json(['error' => $message], 404);
        }

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

        if ($recipes->isEmpty()) {
            $message = $lang === 'es'
                ? 'No se encontraron recetas creadas por ti.'
                : 'No recipes created by you found.';
    
            return response()->json(['error' => $message], 404);
        }

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
            $message = $lang === 'es'
                ? 'Debes proporcionar un nombre de receta.'
                : 'You must provide a recipe name.';
            return response()->json(['error' => $message], 400);
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

        if ($recipes->isEmpty()) {
            $message = $lang === 'es'
                ? 'No se encontraron recetas con ese nombre.'
                : 'No recipes found with that name.';
            return response()->json(['error' => $message], 404);
        }


        return response()->json($recipes);
    }

    /**
     * Get recipes by type.
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getRecipesByType(Request $request)
    {
        $user = $request->user();
        $lang = $request->input('lang', 'es'); // idioma por defecto
        $typeName = $request->input('type');

        if (!$typeName) {
            $message = $lang === 'es'
                ? 'Debes proporcionar un tipo de receta.'
                : 'You must provide a recipe type.';
            return response()->json(['error' => $message], 400);            
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

        if ($recipes->isEmpty()) {
            $message = $lang === 'es'
                ? 'No se encontraron recetas de ese tipo.'
                : 'No recipes found of that type.';
            return response()->json(['error' => $message], 404);
        }

        return response()->json($recipes);
    }
}
