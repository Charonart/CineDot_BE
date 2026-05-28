<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    protected $primaryKey = 'province_id';
    public $timestamps = false;

    const CREATED_AT = 'created_at';

    protected $fillable = [
        'province_name',
        'province_code',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'province_id', 'province_id');
    }

    public function cinemas()
    {
        return $this->hasMany(Cinema::class, 'province_id', 'province_id');
    }
}
