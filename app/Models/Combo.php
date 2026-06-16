<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Combo extends Model
{
    protected $primaryKey = 'combo_id';

    protected $fillable = [
        'name',
        'description',
        'price',
        'image_url',
        'is_active',
    ];

    protected $casts = [
        'price' => 'integer',
        'is_active' => 'boolean',
    ];
}
