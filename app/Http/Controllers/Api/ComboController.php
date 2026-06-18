<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Combo;
use Illuminate\Http\Request;

class ComboController extends Controller
{
    public function index()
    {
        $combos = Combo::where('is_active', true)->get();

        return response()->json([
            'success' => true,
            'data'    => $combos
        ]);
    }
}
