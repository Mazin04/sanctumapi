<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\IngredientTranslation;

class Ingredient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * Get the recipes associated with the ingredient.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function recipes()
    {
        return $this->belongsToMany(Recipe::class, 'recipe_ingredients')
            ->withPivot('quantity')  // Incluye la cantidad en el pivot
            ->withTimestamps();
    }

    public function quantities()
    {
        return $this->hasMany(IngredientQuantity::class);
    }

    public function translations()
    {
        return $this->hasMany(IngredientTranslation::class);
    }
}
