<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\FilterableAndSortable;

class PricingRule extends Model
{
    use FilterableAndSortable;

    protected $primaryKey = 'pricing_rule_id';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'rule_category',
        'conditions',
        'modifier_type',
        'modifier_value',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'conditions'     => 'array',
        'modifier_value' => 'decimal:2',
        'priority'       => 'integer',
        'is_active'      => 'boolean',
    ];
}
