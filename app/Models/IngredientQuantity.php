<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IngredientQuantity extends Model
{
    use HasFactory;

    protected $fillable = [
        'ingredient_id',
        'recipe_id',
        'quantity',
        'language',
    ];

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }    
}
