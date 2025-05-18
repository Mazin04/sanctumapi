<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Recipe;

class PrivateController extends Controller
{
    /**
     * Mark a recipe as private.
     * @param \Illuminate\Http\Request $request
     * @param mixed $id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function makePrivate(Request $request, $id)
    {
        $user = $request->user();
        $lang = $request->input('lang', 'es'); // idioma por defecto (en or es)

        $recipe = Recipe::where('id', $id)->first();

        if (!$recipe) {
            $message = $lang === 'es'
                ? 'Receta no encontrada.'
                : 'Recipe not found.';
            return response()->json(['error' => $message], 404);
        }

        if ($recipe->creator_id !== $user->id) {
            $message = $lang === 'es'
                ? 'No tienes permiso para hacer esto.'
                : 'You do not have permission to do this.';
            return response()->json(['error' => $message], 403);
        }

        $recipe->is_private = true;
        $recipe->save();

        $message = $lang === 'es'
            ? 'Receta marcada como privada.'
            : 'Recipe marked as private.';
        return response()->json(['message' => $message], 200);
    }

    /**
     * Mark a recipe as public.
     * @param \Illuminate\Http\Request $request
     * @param mixed $id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function makePublic(Request $request, $id)
    {
        $user = $request->user();
        $lang = $request->input('lang', 'es'); // idioma por defecto (en or es)

        $recipe = Recipe::where('id', $id)->first();

        if (!$recipe) {
            $message = $lang === 'es'
                ? 'Receta no encontrada.'
                : 'Recipe not found.';
            return response()->json(['error' => $message], 404);
        }

        if ($recipe->creator_id !== $user->id) {
            $message = $lang === 'es'
                ? 'No tienes permiso para hacer esto.'
                : 'You do not have permission to do this.';
            return response()->json(['error' => $message], 403);
        }

        $recipe->is_private = false;
        $recipe->save();

        $message = $lang === 'es'
            ? 'Receta marcada como pública.'
            : 'Recipe marked as public.';
        return response()->json(['message' => $message], 200);
    }
}
