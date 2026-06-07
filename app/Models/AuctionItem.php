<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AuctionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'final_price',
        'status',
    ];

    public function winner(): HasOne
    {
        return $this->hasOne(Winner::class);
    }
}
