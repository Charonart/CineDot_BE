<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointHistory extends Model
{
    protected $table = 'point_histories';
    protected $primaryKey = 'point_histories_id';

    public $timestamps = false;

    const CREATED_AT = 'created_at';

    protected $fillable = [
        'user_id',
        'reference_id',
        'reference_type',
        'amount',
        'action',
    ];

    protected $casts = [
        'amount'     => 'integer',
        'created_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        // Tự động đặt created_at khi tạo mới
        static::creating(function ($model) {
            $model->created_at = $model->freshTimestamp();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Polymorphic: tham chiếu đến Booking, UserVoucher, v.v.
     */
    public function reference()
    {
        return $this->morphTo('reference');
    }
}
