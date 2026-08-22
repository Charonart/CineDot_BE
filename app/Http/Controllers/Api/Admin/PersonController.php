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
        $limit = (int) $request->get('limit', $request->get('per_page', 15));
        if ($limit <= 0) $limit = 15;
        if ($limit > 100) $limit = 100;

        $query = Person::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('original_name', 'like', '%' . $search . '%')
                    ->orWhere('person_id', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        if ($request->filled('department')) {
            $query->where('known_for_department', $request->department);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'person_id');
        $sortDirection = $request->get('sort_dir', $request->get('sort_direction', $request->get('sort_order', 'desc')));
        
        $allowedSorts = [
            'person_id' => 'person_id',
            'id' => 'person_id',
            'name' => 'name',
            'original_name' => 'original_name',
            'originalName' => 'original_name',
            'popularity' => 'popularity',
            'known_for_department' => 'known_for_department',
            'knownForDepartment' => 'known_for_department',
            'birthday' => 'birthday',
            'created_at' => 'created_at',
            'createdAt' => 'created_at',
            'updated_at' => 'updated_at',
        ];

        if (isset($allowedSorts[$sortBy])) {
            $column = $allowedSorts[$sortBy];
            $query->orderBy($column, strtolower($sortDirection) === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('person_id', 'desc');
        }

        $page = (int) $request->get('page', 1);
        $persons = $query->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => [
                'page' => $persons->currentPage(),
                'results' => AdminPersonResource::collection($persons->items()),
                'totalPages' => $persons->lastPage(),
                'totalResults' => $persons->total(),
            ],
            'meta' => [
                'current_page' => $persons->currentPage(),
                'per_page' => $persons->perPage(),
                'total' => $persons->total(),
                'last_page' => $persons->lastPage(),
                'totalResults' => $persons->total(),
                'totalPages' => $persons->lastPage(),
            ],
            'pagination' => [
                'page' => $persons->currentPage(),
                'perPage' => $persons->perPage(),
                'total' => $persons->total(),
                'totalPages' => $persons->lastPage(),
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
