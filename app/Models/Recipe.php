<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\RecipeTranslation;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'creator_id',
        'is_private',
        'is_official',
        'image_path',
    ];


    /**
     * Get the user that created the recipe.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    protected function creator()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the ingredients associated with the recipe.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'recipe_ingredients')
            ->withTimestamps();
    }

    /**
     * Get the ingredient quantities associated with the recipe.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ingredientQuantities()
    {
        return $this->hasMany(IngredientQuantity::class);
    }


    /**
     * Get the types associated with the recipe.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function types()
    {
        return $this->belongsToMany(Type::class, 'recipe_types')
            ->withTimestamps();
    }

    /**
     * Get the steps associated with the recipe.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function steps()
    {
        return $this->hasMany(RecipeStep::class);
    }

    /**
     * Get the favourites associated with the recipe.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function favourites()
    {
        return $this->hasMany(Favourite::class);
    }

    public function translations()
    {
        return $this->hasMany(RecipeTranslation::class);
    }

    public function recipeSteps()
    {
        return $this->hasMany(RecipeStep::class);
    }

    /**
     * Scope a query to filter recipes by the given ingredient IDs.
     *
     * This scope filters recipes that have at least one of the specified ingredients.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder instance.
     * @param array $ingredientsIDs An array of ingredient IDs to filter recipes by.
     * @return \Illuminate\Database\Eloquent\Builder The modified query builder instance.
     */
    public function scopeIngredient($query, array $ingredientsIDs)
    {
        return $query->whereHas('ingredients', function ($query) use ($ingredientsIDs) {
            $query->whereIn('ingredients.id', $ingredientsIDs);
        });
    }

    /**
     * Get the users who favourited the recipe.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function usersWhoFavourited()
    {
        return $this->belongsToMany(User::class, 'favourites')
            ->withTimestamps();
    }
}
