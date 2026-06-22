<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCinemaRequest;
use App\Http\Requests\Admin\UpdateCinemaRequest;
use App\Models\Cinema;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CinemaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Cinema::query();

        if ($request->has('search')) {
            $query->where('cinema_name', 'ilike', '%' . $request->search . '%');
        }

        $cinemas = $query->orderBy('cinema_id', 'desc')->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $cinemas
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCinemaRequest $request)
    {
        $data = $request->validated();
        
        if (!isset($data['slug'])) {
            $data['slug'] = Str::slug($data['cinema_name']) . '-' . time();
        }

        $cinema = Cinema::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Tạo rạp chiếu thành công.',
            'data'    => $cinema
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $cinema = Cinema::with('rooms')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $cinema
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCinemaRequest $request, string $id)
    {
        $cinema = Cinema::findOrFail($id);
        $data = $request->validated();

        if (isset($data['cinema_name']) && $data['cinema_name'] !== $cinema->cinema_name) {
            $data['slug'] = Str::slug($data['cinema_name']) . '-' . time();
        }

        $cinema->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật rạp chiếu thành công.',
            'data'    => $cinema
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $cinema = Cinema::findOrFail($id);
        $cinema->delete(); // Hard delete or standard delete depending on table config (no softDelete column currently)

        return response()->json([
            'success' => true,
            'message' => 'Xóa rạp chiếu thành công.'
        ]);
    }
}
