<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SeatType;
use Illuminate\Http\Request;

class SeatTypeController extends Controller
{
    /**
     * Get active seat types for customer booking selection.
     */
    public function index()
    {
        $seatTypes = \Illuminate\Support\Facades\Cache::remember('seat_types:active_list', 3600, function () {
            return SeatType::where('is_active', true)
                ->orderBy('sort_order', 'asc')
                ->orderBy('surcharge_amount', 'asc')
                ->get();
        });

        return response()->json([
            'success' => true,
            'data'    => $seatTypes,
        ]);
    }
}
