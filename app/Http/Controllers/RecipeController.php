<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Http\Request;
use App\Models\Favourite;
use App\Models\Ingredient;

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
            ->map(function ($recipe) use ($lang) {
                return [
                    'id' => $recipe->id,
                    'name' => $recipe->translations->first()->name ?? ($lang === 'es' ? 'Sin traducción' : 'No translation'),
                    'description' => $recipe->translations->first()->description ?? ($lang === 'es' ? 'Sin traducción' : 'No translation'),
                    'image' => asset($recipe->image_path),
                    'is_official' => $recipe->is_official,
                    'is_private' => $recipe->is_private,
                    'steps_count' => $recipe->recipeSteps->count(),
                    'types' => $recipe->types->map(fn($t) => $t->translations->first()->name ?? ($lang === 'es' ? 'Sin traducción' : 'No translation')),
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
            ->map(function ($recipe) use ($lang) {
                return [
                    'id' => $recipe->id,
                    'name' => $recipe->translations->first()->name ?? ($lang === 'es' ? 'Sin traducción' : 'No translation'),
                    'description' => $recipe->translations->first()->description ?? ($lang === 'es' ? 'Sin traducción' : 'No translation'),
                    'image' => asset($recipe->image_path),
                    'is_official' => $recipe->is_official,
                    'is_private' => $recipe->is_private,
                    'steps_count' => $recipe->recipeSteps->count(),
                    'types' => $recipe->types->map(fn($t) => $t->translations->first()->name ?? ($lang === 'es' ? 'Sin traducción' : 'No translation')),
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
            ->map(function ($recipe) use ($lang) {
                return [
                    'id' => $recipe->id,
                    'name' => $recipe->translations->first()->name ?? ($lang === 'es' ? 'Sin traducción' : 'No translation'),
                    'description' => $recipe->translations->first()->description ?? ($lang === 'es' ? 'Sin traducción' : 'No translation'),
                    'image' => asset($recipe->image_path),
                    'is_official' => $recipe->is_official,
                    'is_private' => $recipe->is_private,
                    'steps_count' => $recipe->recipeSteps->count(),
                    'types' => $recipe->types->map(fn($t) => $t->translations->first()->name ?? ($lang === 'es' ? 'Sin traducción' : 'No translation')),
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
            ->map(function ($recipe) use ($lang) {
                return [
                    'id' => $recipe->id,
                    'name' => $recipe->translations->first()->name ?? ($lang === 'es' ? 'Sin traducción' : 'No translation'),
                    'description' => $recipe->translations->first()->description ?? ($lang === 'es' ? 'Sin traducción' : 'No translation'),
                    'image' => asset($recipe->image_path),
                    'is_official' => $recipe->is_official,
                    'is_private' => $recipe->is_private,
                    'steps_count' => $recipe->recipeSteps->count(),
                    'types' => $recipe->types->map(fn($t) => $t->translations->first()->name ?? ($lang === 'es' ? 'Sin traducción' : 'No translation')),
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
            ->map(function ($recipe) use ($lang) {
                return [
                    'id' => $recipe->id,
                    'name' => $recipe->translations->first()->name ?? ($lang === 'es' ? 'Sin traducción' : 'No translation'),
                    'description' => $recipe->translations->first()->description ?? ($lang === 'es' ? 'Sin traducción' : 'No translation'),
                    'image' => asset($recipe->image_path),
                    'is_official' => $recipe->is_official,
                    'is_private' => $recipe->is_private,
                    'steps_count' => $recipe->recipeSteps->count(),
                    'types' => $recipe->types->map(fn($t) => $t->translations->first()->name ?? ($lang === 'es' ? 'Sin traducción' : 'No translation')),
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
            ->map(function ($recipe) use ($lang) {
                return [
                    'id' => $recipe->id,
                    'name' => $recipe->translations->first()->name ?? ($lang === 'es' ? 'Sin traducción' : 'No translation'),
                    'description' => $recipe->translations->first()->description ?? ($lang === 'es' ? 'Sin traducción' : 'No translation'),
                    'image' => asset($recipe->image_path),
                    'is_official' => $recipe->is_official,
                    'is_private' => $recipe->is_private,
                    'steps_count' => $recipe->recipeSteps->count(),
                    'types' => $recipe->types->map(fn($t) => $t->translations->first()->name ?? ($lang === 'es' ? 'Sin traducción' : 'No translation')),
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

    public function store(Request $request)
    {
        $user = $request->user();
        $lang = $request->input('lang', 'es'); // idioma por defecto (en or es)

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'ingredients' => 'required|array|min:1',
            'ingredients.*.id' => 'required|integer|exists:ingredients,id',
            'ingredients.*.quantity' => 'required|string|max:255',
            'ingredients.*.language' => 'required|string|max:2',
            'steps' => 'required|array|min:1',
            'steps.*' => 'string',
            'types' => 'required|array|min:1',
            'types.*' => 'integer|exists:types,id',
            'is_private' => 'required|boolean',
            'is_official' => 'required|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Procesar la imagen (si existe)
        $imagePath = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = uniqid() . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('recipes', $imageName, 'public');
            $imagePath = '/storage/' . $imagePath;
        }

        $recipe = Recipe::create([
            'creator_id' => $user->id,
            'is_private' => $validated['is_private'],
            'is_official' => $validated['is_official'],
            'image_path' => $imagePath,
        ]);

        $recipe->translations()->create([
            'language' => $lang,
            'name' => $validated['name'],
            'description' => $validated['description'],
        ]);

        foreach ($validated['steps'] as $index => $step) {
            $recipeStep = $recipe->steps()->create([
                'step_number' => $index + 1,
            ]);

            $recipeStep->translations()->create([
                'language' => 'es',
                'step_description' => $step,
            ]);
        }

        foreach ($validated['ingredients'] as $ingredient) {
            // Asociamos el ingrediente y la cantidad
            $ingredientModel = Ingredient::find($ingredient['id']);
            $ingredientModel->quantities()->create([
                'quantity' => $ingredient['quantity'],
                'language' => $ingredient['language'],
                'recipe_id' => $recipe->id,
            ]);
        }

        $recipe->types()->attach($validated['types']);

        return response()->json(['message' => 'Receta creada con éxito.', 'recipe' => $recipe]);
    }
}
