<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\FilterableAndSortable;

class Campaign extends Model
{
    use FilterableAndSortable;

    protected $primaryKey = 'campaign_id';

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'budget',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'budget'     => 'decimal:2',
        'is_active'  => 'boolean',
    ];

    public function vouchers()
    {
        return $this->hasMany(Voucher::class, 'campaign_id', 'campaign_id');
    }

    public function banners()
    {
        return $this->hasMany(Banner::class, 'campaign_id', 'campaign_id');
    }
}
