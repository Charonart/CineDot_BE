<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Person;
use App\Http\Requests\Admin\StorePersonRequest;
use App\Http\Requests\Admin\UpdatePersonRequest;
use App\Http\Resources\AdminPersonResource;
use Illuminate\Http\Request;

class PersonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = $request->get('limit', 15);
        $query = Person::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', '%' . $search . '%')
                    ->orWhere('original_name', 'ilike', '%' . $search . '%');
            });
        }

        if ($request->has('gender')) {
            $query->where('gender', $request->gender);
        }

        if ($request->has('department')) {
            $query->where('known_for_department', $request->department);
        }

        $persons = $query->orderBy('person_id', 'desc')->paginate($limit);

        return response()->json([
            'success' => true,
            'data' => [
                'page' => $persons->currentPage(),
                'results' => AdminPersonResource::collection($persons->items()),
                'totalPages' => $persons->lastPage(),
                'totalResults' => $persons->total(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePersonRequest $request)
    {
        $person = Person::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tạo diễn viên/đạo diễn thành công.',
            'data' => new AdminPersonResource($person)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $person = Person::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new AdminPersonResource($person)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePersonRequest $request, string $id)
    {
        $person = Person::findOrFail($id);
        $person->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật diễn viên/đạo diễn thành công.',
            'data' => new AdminPersonResource($person)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $person = Person::findOrFail($id);
        $person->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa diễn viên/đạo diễn thành công.'
        ]);
    }
}
