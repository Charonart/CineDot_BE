<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\FilterableAndSortable;

class Banner extends Model
{
    use FilterableAndSortable;

    protected $table = 'banners';
    protected $primaryKey = 'banner_id';

    protected $fillable = [
        'campaign_id',
        'title',
        'image_url',
        'link_url',
        'order',
        'is_active',
    ];

    protected $casts = [
        'order'     => 'integer',
        'is_active' => 'boolean',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'campaign_id');
    }
}
