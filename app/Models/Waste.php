<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Waste extends Model
{
    use HasFactory;
    
   protected $fillable = [
    'waste_type',
    'user_id',
    'weight',
    'date',
    'shift',
    'status',
    'latitude',
    'longitude',
];
protected $attributes = [
    'status' => 'pending',
];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

}
