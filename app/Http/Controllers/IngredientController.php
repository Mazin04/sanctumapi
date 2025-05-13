<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Ingredient;

class IngredientController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $lang = $request->input('lang', 'es');
        $ingredients = $user->ingredients()->with(['translations' => function ($query) use ($lang) {
            $query->where('language', $lang);
        }])->get()
        ->map(function ($ingredient) use ($lang) {
            return [
                'id' => $ingredient->id,
                'name' => $ingredient->translations->firstWhere('language', $lang)->name ?? $ingredient->name,
                'quantity' => $ingredient->pivot->quantity,
                'unit' => $ingredient->pivot->unit,
            ];
        });
        // Check if the user has ingredients
        if ($ingredients->isEmpty()) {
            $message = $lang === 'es' ? 'No tienes ingredientes' : 'You have no ingredients';
            return response()->json(['message' => $message], 201);
        }
        return response()->json($ingredients);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $lang = $request->input('lang', 'es');
        $ingredientId = $request->input('ingredient_id');
        $quantity = $request->input('quantity');
        $unit = $request->input('unit');

        // Check if the ingredient already exists for the user
        if ($user->ingredients()->where('ingredient_id', $ingredientId)->exists()) {
            $message = $lang === 'es' ? 'El ingrediente ya existe en tu lista' : 'The ingredient already exists in your list';
            return response()->json(['message' => $message], 404);
        }

        // Validate the input
        $validator = Validator::make($request->all(), [
            'ingredient_id' => 'required|exists:ingredients,id',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        // Check if the ingredient exists
        $ingredient = Ingredient::find($ingredientId);
        if (!$ingredient) {
            $message = $lang === 'es' ? 'Ingrediente no encontrado' : 'Ingredient not found';
            return response()->json(['message' => $message], 404);
        }


        // Attach the ingredient to the user with the specified quantity and unit
        $user->ingredients()->attach($ingredientId, ['quantity' => $quantity, 'unit' => $unit]);

        $message = $lang === 'es' ? 'Ingrediente añadido' : 'Ingredient added';
        return response()->json(['message' => $message], 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $ingredient = $user->ingredients()->find($id);
        if (!$ingredient) {
            $message = $request->input('lang', 'es') === 'es' ? 'Ingrediente no encontrado' : 'Ingredient not found';
            return response()->json(['message' => $message], 404);
        }

        $newQuantity = $request->input('quantity');
        $newUnit = $request->input('unit');
        if ($newQuantity) {
            $ingredient->pivot->quantity = $newQuantity;
        }
        if ($newUnit) {
            $ingredient->pivot->unit = $newUnit;
        }
        $ingredient->save();
        $ingredient->pivot->save();

        $message = $request->input('lang', 'es') === 'es' ? 'Ingrediente actualizado' : 'Ingredient updated';
        return response()->json(['message' => $message], 200);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $ingredient = $user->ingredients()->find($id);
        if (!$ingredient) {
            $message = $request->input('lang', 'es') === 'es' ? 'Ingrediente no encontrado' : 'Ingredient not found';
            return response()->json(['message' => $message], 404);
        }

        $user->ingredients()->detach($id);
        
        $message = $request->input('lang', 'es') === 'es' ? 'Ingrediente eliminado' : 'Ingredient deleted';
        return response()->json(['message' => $message], 200);
    }

    public function destroyAll(Request $request)
    {
        $user = $request->user();
        $user->ingredients()->detach();
        $message = $request->input('lang', 'es') === 'es' ? 'Todos los ingredientes eliminados' : 'All ingredients deleted';
        return response()->json(['message' => $message], 200);
    }
}
