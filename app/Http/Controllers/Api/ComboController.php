<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Combo;
use Illuminate\Http\Request;

class ComboController extends Controller
{
    public function index()
    {
        $combos = \Illuminate\Support\Facades\Cache::remember('combos:active', 3600, function () {
            return Combo::where('is_active', true)->get();
        });

        return response()->json([
            'success' => true,
            'data'    => $combos
        ]);
    }
}
