<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Favourite;

class FavouriteController extends Controller
{
    /**
     * Remove a recipe from the user's favourites.
     * @param \Illuminate\Http\Request $request
     * @param mixed $id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function removeFromFavourites(Request $request, $id)
    {
        $user = $request->user();
        $lang = $request->input('lang', 'es'); // idioma por defecto (en or es)

        $favourite = Favourite::where('user_id', $user->id)
            ->where('recipe_id', $id)
            ->first();

        if (!$favourite) {
            $message = $lang === 'es'
                ? 'Favorito no encontrado.'
                : 'Favourite not found.';
            return response()->json(['error' => $message], 404);
        }

        $favourite->delete();

        $message = $lang === 'es'
            ? 'Receta eliminada de tus favoritos.'
            : 'Recipe removed from your favourites.';
        return response()->json(['message' => $message], 200);
    }

    /**
     * Add a recipe to the user's favourites.
     * @param \Illuminate\Http\Request $request
     * @param mixed $id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function addToFavourites(Request $request, $id)
    {
        $user = $request->user();
        $lang = $request->input('lang', 'es'); // idioma por defecto (en or es)

        $exists = Favourite::where('user_id', $user->id)
        ->where('recipe_id', $id)
        ->first();

        if ($exists) {
            $message = $lang === 'es'
                ? 'Receta ya está en tus favoritos.'
                : 'Recipe already in your favourites.';
            return response()->json(['error' => $message], 400);
        }

        Favourite::create([
            'user_id'=> $user->id,
            'recipe_id'=> $id
        ]);

        $message = $lang ===  'es'
            ? 'Receta añadida a tus favoritos.'
            : 'Recipe added to your favourites.';
            return response()->json(['message'=> $message], 200);
    }}
