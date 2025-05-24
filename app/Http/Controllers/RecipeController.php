<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Http\Request;
use App\Models\Favourite;
use App\Models\Ingredient;
use App\Models\IngredientQuantity;
use App\Models\RecipeIngredient;
use Illuminate\Support\Facades\DB;
use App\Models\UserIngredient;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class RecipeController extends Controller
{
    /**
     * Gets the ingredient match status for a recipe based on user ingredients.
     * @param mixed $recipe
     * @param mixed $userIngredients
     * @param mixed $lang
     * @return string
     */
    public function getIngredientMatch($recipe, $userIngredients, $lang = 'es')
    {
        $sufficient = 0;
        $insufficient = 0;
        $missing = 0;
        $unitMismatch = 0;

        foreach ($recipe->ingredientsQuantities as $ingredientQuantity) {
            $ingredientId = $ingredientQuantity->ingredient_id;
            $requiredQuantity = $ingredientQuantity->quantity;
            $requiredUnit = strtolower($ingredientQuantity->unit);

            // Omitir ingredientes cuya unidad es 'taste'
            if ($requiredUnit === 'taste') {
                continue;
            }

            $userIngredient = $userIngredients->get($ingredientId);

            if (!$userIngredient) {
                $missing++;
                continue;
            }

            $userUnit = strtolower($userIngredient->pivot->unit);

            if ($userUnit !== $requiredUnit) {
                $unitMismatch++;
                continue;
            }

            if ($userIngredient->pivot->quantity >= $requiredQuantity) {
                $sufficient++;
            } else {
                $insufficient++;
            }
        }

        if ($missing > 0) {
            $ingredientsMatch = $lang === 'es' ? 'NO TIENE' : 'MISSING';
        } elseif ($unitMismatch > 0) {
            $ingredientsMatch = $lang === 'es' ? 'UNIDADES DISTINTAS' : 'DIFFERENT UNITS';
        } elseif ($insufficient > 0) {
            $ingredientsMatch = $lang === 'es' ? 'NO SUFICIENTE' : 'NOT ENOUGH';
        } else {
            $ingredientsMatch = $lang === 'es' ? 'PUEDE HACERLO' : 'CAN MAKE';
        }

        return $ingredientsMatch;
    }


    /**
     * Filter recipes by ingredients.
     * This method retrieves recipes that contain at least one of the specified ingredients.
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function filterByIngredient(Request $request)
    {
        $user = $request->user();
        $lang = $request->input('lang', 'es'); // idioma por defecto

        $ingredientIDs = $request->input('ingredients');

        if (!is_array($ingredientIDs) || empty($ingredientIDs)) {
            $message = $lang === 'es'
                ? 'Debes proporcionar al menos un ingrediente.'
                : 'You must provide at least one ingredient.';

            return response()->json(['error' => $message], 400);
        }

        // Buscamos recetas que contengan los ingredientes pedidos
        $recipes = Recipe::whereHas('ingredients', function ($query) use ($ingredientIDs) {
            $query->whereIn('ingredient_id', $ingredientIDs);
        })
            ->where(function ($query) use ($user) {
                $query->where('is_private', false)
                    ->orWhere('creator_id', $user->id)
                    ->orWhereHas('usersWhoFavourited', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
            })
            ->with([
                'ingredients' => function ($query) {
                    $query->select('ingredients.id');
                },
                'ingredientsQuantities',
                'translations' => function ($query) use ($lang) {
                    $query->where('language', $lang);
                },
                'recipeSteps',
                'types.translations' => function ($query) use ($lang) {
                    $query->where('language', $lang);
                }
            ])
            ->get()
            ->map(function ($recipe) use ($user, $lang) {
                $ingredientsMatch = $this->getIngredientMatch($recipe, $user->ingredients->keyBy('id'), $lang);
                return [
                    'id' => $recipe->id,
                    'name' => $recipe->translations->first()->name ?? ($lang === 'es' ? 'Sin traducción' : 'No translation'),
                    'description' => $recipe->translations->first()->description ?? ($lang === 'es' ? 'Sin traducción' : 'No translation'),
                    'image' => $recipe->image_path ? asset($recipe->image_path) : null,
                    'is_official' => $recipe->is_official,
                    'is_private' => $recipe->is_private,
                    'steps_count' => $recipe->recipeSteps->count(),
                    'types' => $recipe->types->map(fn($t) => $t->translations->first()->name ?? ($lang === 'es' ? 'Sin traducción' : 'No translation')),
                    'ingredients_match' => $ingredientsMatch,
                ];
            });

        if ($recipes->isEmpty()) {
            $message = $lang === 'es'
                ? 'No se encontraron recetas con esos ingredientes.'
                : 'No recipes found with those ingredients.';

            return response()->json(['error' => $message], 400);
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

        $recipes = Recipe::where('is_private', false)
            ->orWhere('creator_id', $user->id)
            ->orWhereHas('usersWhoFavourited', function ($q) use ($user) {
                $q->where('user_id', $user->id);
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
            ->map(function ($recipe) use ($lang, $user) {
                $ingredientsMatch = $this->getIngredientMatch($recipe, $user->ingredients->keyBy('id'), $lang);

                return [
                    'id' => $recipe->id,
                    'name' => $recipe->translations->first()->name ?? ($lang === 'es' ? 'Sin traducción' : 'No translation'),
                    'description' => $recipe->translations->first()->description ?? ($lang === 'es' ? 'Sin traducción' : 'No translation'),
                    'image' => $recipe->image_path ? asset($recipe->image_path) : null,
                    'is_official' => $recipe->is_official,
                    'is_private' => $recipe->is_private,
                    'steps_count' => $recipe->recipeSteps->count(),
                    'types' => $recipe->types->map(fn($t) => $t->translations->first()->name ?? ($lang === 'es' ? 'Sin traducción' : 'No translation')),
                    'ingredients_match' => $ingredientsMatch,
                ];
            });

        if ($recipes->isEmpty()) {
            $message = $lang === 'es'
                ? 'No se encontraron recetas.'
                : 'No recipes found.';

            return response()->json(['message' => $message, 'recipes' => []], 200);
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
            ->map(function ($recipe) use ($lang, $user) {
                $ingredientsMatch = $this->getIngredientMatch($recipe, $user->ingredients->keyBy('id'), $lang);
                return [
                    'id' => $recipe->id,
                    'name' => $recipe->translations->first()->name ?? ($lang === 'es' ? 'Sin traducción' : 'No translation'),
                    'description' => $recipe->translations->first()->description ?? ($lang === 'es' ? 'Sin traducción' : 'No translation'),
                    'image' => $recipe->image_path ? asset($recipe->image_path) : null,
                    'is_official' => $recipe->is_official,
                    'is_favourite' => $recipe->usersWhoFavourited->contains($user->id),
                    'is_private' => $recipe->is_private,
                    'steps_count' => $recipe->recipeSteps->count(),
                    'types' => $recipe->types->map(fn($t) => $t->translations->first()->name ?? ($lang === 'es' ? 'Sin traducción' : 'No translation')),
                    'ingredients_match' => $ingredientsMatch,
                ];
            });

        if ($recipes->isEmpty()) {
            $message = $lang === 'es'
                ? 'No se encontraron recetas favoritas.'
                : 'No favourite recipes found.';

            return response()->json(['message' => $message, 'recipes' => []], 200);
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
            ->map(function ($recipe) use ($lang, $user) {
                $ingredientsMatch = $this->getIngredientMatch($recipe, $user->ingredients->keyBy('id'), $lang);
                return [
                    'id' => $recipe->id,
                    'name' => $recipe->translations->first()->name ?? ($lang === 'es' ? 'Sin traducción' : 'No translation'),
                    'description' => $recipe->translations->first()->description ?? ($lang === 'es' ? 'Sin traducción' : 'No translation'),
                    'image' => $recipe->image_path ? asset($recipe->image_path) : null,
                    'is_official' => $recipe->is_official,
                    'is_private' => $recipe->is_private,
                    'is_favourite' => $recipe->usersWhoFavourited->contains($user->id),
                    'steps_count' => $recipe->recipeSteps->count(),
                    'types' => $recipe->types->map(fn($t) => $t->translations->first()->name ?? ($lang === 'es' ? 'Sin traducción' : 'No translation')),
                    'ingredients_match' => $ingredientsMatch,
                ];
            });
        if ($recipes->isEmpty()) {
            $message = $lang === 'es'
                ? 'No se encontraron recetas creadas por ti.'
                : 'No recipes created by you found.';

            return response()->json(['message' => $message, 'recipes' => []], 200);
        }

        return response()->json($recipes);
    }

    /**
     * Get all public recipes created by a user.
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getPublicRecipes(Request $request, $id)
    {
        $lang = $request->input('lang', 'es');
        $user = User::find($id);
        if (!$user) {
            $message = $lang === 'es' ? 'Usuario no encontrado' : 'User not found';
            return response()->json(['message' => $message], 404);
        }

        $publicRecipes = Recipe::where('creator_id', $id)
            ->where('is_private', false)
            ->with(['ingredients', 'types', 'steps'])
            ->get()
            ->map(function ($recipe) use ($lang, $user) {
                $ingredientsMatch = $this->getIngredientMatch($recipe, $user->ingredients->keyBy('id'), $lang);
                return [
                    'id' => $recipe->id,
                    'name' => $recipe->translations->first()->name ?? ($lang === 'es' ? 'Sin traducción' : 'No translation'),
                    'description' => $recipe->translations->first()->description ?? ($lang === 'es' ? 'Sin traducción' : 'No translation'),
                    'image' => $recipe->image_path ? asset($recipe->image_path) : null,
                    'is_official' => $recipe->is_official,
                    'is_private' => $recipe->is_private,
                    'is_favourite' => $recipe->usersWhoFavourited->contains($user->id),
                    'steps_count' => $recipe->recipeSteps->count(),
                    'types' => $recipe->types->map(fn($t) => $t->translations->first()->name ?? ($lang === 'es' ? 'Sin traducción' : 'No translation')),
                    'ingredients_match' => $ingredientsMatch,
                ];
            });
        return response()->json($publicRecipes);
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
        $user = $request->user();

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
            ->where(function ($query) use ($user) {
                $query->where('is_private', false)
                    ->orWhere('creator_id', $user->id)
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
            ->map(function ($recipe) use ($lang, $user) {
                $ingredientsMatch = $this->getIngredientMatch($recipe, $user->ingredients->keyBy('id'), $lang);
                return [
                    'id' => $recipe->id,
                    'name' => $recipe->translations->first()->name ?? ($lang === 'es' ? 'Sin traducción' : 'No translation'),
                    'description' => $recipe->translations->first()->description ?? ($lang === 'es' ? 'Sin traducción' : 'No translation'),
                    'image' => $recipe->image_path ? asset($recipe->image_path) : null,
                    'is_official' => $recipe->is_official,
                    'is_private' => $recipe->is_private,
                    'steps_count' => $recipe->recipeSteps->count(),
                    'types' => $recipe->types->map(fn($t) => $t->translations->first()->name ?? ($lang === 'es' ? 'Sin traducción' : 'No translation')),
                    'ingredients_match' => $ingredientsMatch,
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
     * Recipes must be public or favourited by the user.
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
            $query->where('is_private', false)
                ->orWhere('creator_id', $user->id)
                ->orWhereHas('usersWhoFavourited', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
        })
            ->whereHas('types.translations', function ($query) use ($typeName, $lang) {
                $query->where('language', $lang)
                    ->where('name', 'like', '%' . $typeName . '%');
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
            ->map(function ($recipe) use ($lang, $user) {
                $ingredientsMatch = $this->getIngredientMatch($recipe, $user->ingredients->keyBy('id'), $lang);
                return [
                    'id' => $recipe->id,
                    'name' => $recipe->translations->first()->name ?? ($lang === 'es' ? 'Sin traducción' : 'No translation'),
                    'description' => $recipe->translations->first()->description ?? ($lang === 'es' ? 'Sin traducción' : 'No translation'),
                    'image' => $recipe->image_path ? asset($recipe->image_path) : null,
                    'is_official' => $recipe->is_official,
                    'is_private' => $recipe->is_private,
                    'steps_count' => $recipe->recipeSteps->count(),
                    'types' => $recipe->types->map(fn($t) => $t->translations->first()->name ?? ($lang === 'es' ? 'Sin traducción' : 'No translation')),
                    'ingredients_match' => $ingredientsMatch,
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

    /**
     * Get recipes available for the authenticated user.
     * This method retrieves recipes that the user can make based on their ingredients.
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function recipesAvailableForUser(Request $request)
    {
        $user = $request->user();
        $lang = $request->input('lang', 'es');
        $perPage = (int) $request->input('perPage', 21);
        $page = (int) $request->input('page', 1);

        // Obtener los IDs de ingredientes que tiene el usuario
        $userIngredientIds = UserIngredient::where('user_id', $user->id)
            ->pluck('ingredient_id')
            ->toArray();

        // Obtener todas las recetas
        $recipes = Recipe::with(['ingredients', 'translations', 'types.translations', 'recipeSteps'])
            ->where(function ($query) use ($user) {
                $query->where('is_private', false)
                    ->orWhere('creator_id', $user->id)
                    ->orWhereHas('usersWhoFavourited', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
            })
            ->get();

        // Filtrar recetas disponibles
        $availableRecipes = $recipes->filter(function ($recipe) use ($user) {
            $userIngredients = $user->ingredients->keyBy('id');
            $ingredientsMatch = $this->getIngredientMatch($recipe, $userIngredients);
            return $ingredientsMatch === 'PUEDE HACERLO' || $ingredientsMatch === 'CAN MAKE';
        });

        if ($availableRecipes->isEmpty()) {
            $message = $lang === 'es'
                ? 'No tienes ingredientes suficientes para ninguna receta.'
                : 'You do not have enough ingredients for any recipe.';

            return response()->json(['error' => $message], 404);
        }

        // Paginación manual
        $sliced = $availableRecipes->forPage($page, $perPage)->values();
        $paginated = new LengthAwarePaginator(
            $sliced,
            $availableRecipes->count(),
            $perPage,
            $page,
        );

        $paginated->setCollection($sliced->map(function ($recipe) use ($user) {
            return [
                'id' => $recipe->id,
                'name' => $recipe->translations->first()->name ?? 'Sin traducción',
                'description' => $recipe->translations->first()->description ?? 'Sin traducción',
                'image' => $recipe->image_path ? asset($recipe->image_path) : null,
                'is_official' => $recipe->is_official,
                'is_private' => $recipe->is_private,
                'is_favourite' => $recipe->usersWhoFavourited->contains($user->id),
                'steps_count' => $recipe->recipeSteps->count(),
                'types' => $recipe->types->map(fn($t) => $t->translations->first()->name ?? 'Sin traducción'),
                'ingredients_match' => $this->getIngredientMatch($recipe, $user->ingredients->keyBy('id')),
            ];
        }));

        return response()->json($paginated);
    }

    /**
     * Store a new recipe.
     * This method creates a new recipe with its translations, steps, and ingredients.
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $lang = $request->input('lang', 'es'); // idioma por defecto (en or es)

        $validated = $request->validate([
            'names' => 'required|array|min:1',
            'names.*.language' => 'required|string|max:2',
            'names.*.name' => 'required|string|max:255',
            'descriptions' => 'required|array|min:1',
            'descriptions.*.language' => 'required|string|max:2',
            'descriptions.*.description' => 'required|string',
            'ingredients' => 'required|array|min:1',
            'ingredients.*.id' => 'required|integer|exists:ingredients,id',
            'ingredients.*.quantity' => 'required|string|max:255',
            'ingredients.*.unit' => 'required|string|max:20',
            'steps' => 'required|array|min:1',
            'steps.*.language' => 'required|string|max:2',
            'steps.*.order' => 'required|integer',
            'steps.*.step' => 'required|string',
            'types' => 'required|array|min:1',
            'types.*' => 'integer|exists:types,id',
            'is_private' => 'required|boolean',
            'is_official' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        // Procesar imagen (si existe)
        $imagePath = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = uniqid() . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('recipes', $imageName, 'public');
            $imagePath = "/storage/{$imagePath}";
        }

        // Crear la receta
        $recipe = Recipe::create([
            'creator_id' => $user->id,
            'is_private' => $validated['is_private'],
            'is_official' => false,
            'image_path' => $imagePath ?? null,
        ]);

        // Guardar traducciones del nombre y descripción
        foreach ($validated['names'] as $index => $nameData) {
            $descriptionData = collect($validated['descriptions'])->firstWhere('language', $nameData['language']);
            $recipe->translations()->create([
                'language' => $nameData['language'],
                'name' => $nameData['name'],
                'description' => $descriptionData ? $descriptionData['description'] : '',
            ]);
        }

        // Agrupar pasos por número (order) y luego traducirlos
        $stepsGrouped = collect($validated['steps'])->groupBy('order');
        foreach ($stepsGrouped as $stepNumber => $stepTranslations) {
            $recipeStep = $recipe->steps()->create([
                'step_number' => $stepNumber,
            ]);

            foreach ($stepTranslations as $translation) {
                $recipeStep->translations()->create([
                    'language' => $translation['language'],
                    'step_description' => $translation['step'],
                ]);
            }
        }

        // Asociar ingredientes (por idioma, pero una sola relación con la receta)
        $uniqueIngredients = collect($validated['ingredients'])->pluck('id')->unique();
        foreach ($uniqueIngredients as $ingredientId) {
            $recipe->ingredients()->attach($ingredientId);
        }

        // Guardar cantidades por idioma
        foreach ($validated['ingredients'] as $ingredient) {
            IngredientQuantity::create([
                'ingredient_id' => $ingredient['id'],
                'recipe_id' => $recipe->id,
                'quantity' => $ingredient['quantity'],
                'unit' => $ingredient['unit'] ?? null,
            ]);
        }

        // Asociar tipos
        $recipe->types()->attach($validated['types']);

        $message = $lang === 'es'
            ? 'Receta creada con éxito.'
            : 'Recipe created successfully.';
        return response()->json(['success' => $message, 'recipe' => $recipe], 201);
    }

    /**
     * Update an existing recipe.
     * This method updates the recipe's translations, steps, and ingredients.
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Recipe $recipe
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $lang = $request->input('lang', 'es'); // idioma por defecto (en or es)

        $recipe = Recipe::find($id);
        if ($recipe === null) {
            $message = $lang === 'es'
                ? 'Receta no encontrada.'
                : 'Recipe not found.';
            return response()->json(['error' => $message], 404);
        }
        if ($recipe->creator_id !== $user->id) {
            $message = $lang === 'es'
                ? 'No tienes permiso para editar esta receta.'
                : 'You do not have permission to edit this recipe.';
            return response()->json(['error' => $message], 403);
        }

        // Validar los datos de entrada
        $validated = $request->validate([
            'names' => 'required|array|min:1',
            'names.*.language' => 'required|string|in:es,en',
            'names.*.name' => 'required|string|max:255',

            'descriptions' => 'required|array|min:1',
            'descriptions.*.language' => 'required|string|in:es,en',
            'descriptions.*.description' => 'required|string',

            'ingredients' => 'required|array|min:1',
            'ingredients.*.id' => 'required|integer|exists:ingredients,id',
            'ingredients.*.quantity' => 'required|string|max:255',
            'ingredients.*.unit' => 'required|string|max:20',

            'steps' => 'required|array|min:1',
            'steps.*.language' => 'required|string|in:es,en',
            'steps.*.order' => 'required|integer',
            'steps.*.step' => 'required|string',

            'types' => 'required|array|min:1',
            'types.*' => 'required|integer|exists:types,id',

            'is_private' => 'required|boolean',
            'is_official' => 'required|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096',
        ]);

        // Si hay nueva imagen
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = uniqid() . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('recipes', $imageName, 'public');
            $imagePath = '/storage/' . $imagePath;
        } else {
            $imagePath = $recipe->image_path;
        }

        // Actualizar receta principal
        $recipe->update([
            'is_private' => $validated['is_private'],
            'is_official' => $validated['is_official'],
            'image_path' => $imagePath,
        ]);

        // 1. Limpiar traducciones y recrear
        $recipe->translations()->delete();
        foreach ($validated['names'] as $nameData) {
            $lang = $nameData['language'];
            $name = $nameData['name'];
            $desc = collect($validated['descriptions'])->firstWhere('language', $lang)['description'] ?? '';
            $recipe->translations()->create([
                'language' => $lang,
                'name' => $name,
                'description' => $desc,
            ]);
        }

        // 2. Limpiar relaciones de ingredientes
        DB::table('recipe_ingredients')->where('recipe_id', $recipe->id)->delete();
        IngredientQuantity::where('recipe_id', $recipe->id)->delete();

        // Evitar duplicados en recipe_ingredients
        $uniqueIngredientIds = collect($validated['ingredients'])
            ->pluck('id')
            ->unique();

        foreach ($uniqueIngredientIds as $ingredientId) {
            DB::table('recipe_ingredients')->insert([
                'recipe_id' => $recipe->id,
                'ingredient_id' => $ingredientId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Insertar cantidades por idioma
        foreach ($validated['ingredients'] as $ing) {
            IngredientQuantity::create([
                'ingredient_id' => $ing['id'],
                'recipe_id' => $recipe->id,
                'quantity' => $ing['quantity'],
                'unit' => $ing['unit'],
            ]);
        }

        // 3. Limpiar pasos y sus traducciones
        $recipe->steps->each(function ($step) {
            $step->translations()->where('recipe_step_id', $step->id)->delete();
        });
        $recipe->steps()->where('recipe_id', $recipe->id)->delete();

        $stepGroups = collect($validated['steps'])->groupBy('order');
        foreach ($stepGroups as $order => $translations) {
            $step = $recipe->steps()->create(['step_number' => $order]);
            foreach ($translations as $trans) {
                $step->translations()->create([
                    'language' => $trans['language'],
                    'step_description' => $trans['step'],
                ]);
            }
        }

        // Actualizar tipos
        $recipe->types()->sync($validated['types']);

        $message = $lang === "es"
            ? 'Receta actualizada con éxito.'
            : 'Recipe updated successfully.';
        return response()->json([
            'message' => $message,
            'recipe' => $recipe->load([
                'translations',
                'recipeSteps.translations',
                'ingredients',
                'types.translations'
            ])
        ]);
    }

    /**
     * Delete a recipe.
     * This method deletes the recipe and its associated data.
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $lang = $request->input('lang', 'es'); // idioma por defecto (en or es)

        $recipe = Recipe::find($id);
        if ($recipe === null) {
            $message = $lang === 'es'
                ? 'Receta no encontrada.'
                : 'Recipe not found.';
            return response()->json(['error' => $message], 404);
        }
        if ($recipe->creator_id !== $user->id) {
            $message = $lang === 'es'
                ? 'No tienes permiso para eliminar esta receta.'
                : 'You do not have permission to delete this recipe.';
            return response()->json(['error' => $message], 403);
        }

        $recipe->delete();

        $message = $lang === 'es'
            ? 'Receta eliminada con éxito.'
            : 'Recipe deleted successfully.';
        return response()->json(['message' => $message], 200);
    }

    /**
     * Show a specific recipe.
     * This method retrieves a recipe by its ID and checks permissions.
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $lang = $request->input('lang', 'es'); // Idioma por defecto

        $recipe = Recipe::with([
            'translations' => fn($q) => $q->where('language', $lang),
            'recipeSteps.translations' => fn($q) => $q->where('language', $lang),
            'ingredients.translations' => fn($q) => $q->where('language', $lang),
            'types.translations' => fn($q) => $q->where('language', $lang),
            'ingredientsQuantities',
            'creator'
        ])->find($id);

        if ($recipe === null) {
            $message = $lang === 'es'
                ? 'Receta no encontrada.'
                : 'Recipe not found.';
            return response()->json(['error' => $message], 404);
        }

        if (
            $recipe->is_private &&
            $recipe->creator_id !== $user->id &&
            !$recipe->is_official &&
            !$recipe->usersWhoFavourited->contains($user->id)
        ) {
            $message = $lang === 'es'
                ? 'No tienes permiso para ver esta receta.'
                : 'You do not have permission to view this recipe.';
            return response()->json(['error' => $message], 403);
        }

        $translation = $recipe->translations->first();

        $response = [
            'id' => $recipe->id,
            'name' => $translation->name ?? '',
            'description' => $translation->description ?? '',
            'image' => $recipe->image_path ? asset($recipe->image_path) : null,
            'is_official' => $recipe->is_official,
            'creator' => [
                'id' => $recipe->creator_id,
                'name' => $recipe->creator->name ?? '',
            ],
            'is_private' => $recipe->is_private,
            'is_favourite' => $recipe->usersWhoFavourited->contains($user->id),
            'types' => $recipe->types->pluck('translations')->flatten()->pluck('name'),
            'ingredients' => $recipe->ingredients->map(function ($ingredient) use ($recipe) {
                $quantity = $recipe->ingredientsQuantities
                    ->where('ingredient_id', $ingredient->id)
                    ->first();
                return [
                    'name' => $ingredient->translations->first()->name ?? '',
                    'quantity' => $quantity->quantity ?? '',
                    'unit' => $quantity->unit ?? '',
                ];
            }),

            'steps' => $recipe->recipeSteps->sortBy('step_number')->map(function ($step) {
                $trans = $step->translations->first();
                return $trans?->step_description ?? '';
            })->values(),
            'ingredients_match' => $this->getIngredientMatch($recipe, $user->ingredients->keyBy('id'), $lang),
        ];

        return response()->json($response);
    }
}
