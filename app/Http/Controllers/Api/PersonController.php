<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Person;
use App\Http\Resources\PersonResource;

class PersonController extends Controller
{
    public function show($id)
    {
        $person = Person::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data'    => new PersonResource($person)
        ]);
    }
}
