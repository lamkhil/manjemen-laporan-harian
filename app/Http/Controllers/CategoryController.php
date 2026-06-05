<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = Category::query()->orderBy('sort_order')->orderBy('name');

        if ($request->boolean('active_only')) {
            $q->where('is_active', true);
        }
        if ($s = $request->string('search')->toString()) {
            $q->where('name', 'ilike', '%' . $s . '%');
        }

        return response()->json($q->get());
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'color' => 'nullable|string|max:16',
            'icon' => 'nullable|string|max:32',
            'is_violation' => 'boolean',
            'is_service' => 'boolean',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $category = Category::create($data);

        return response()->json($category, 201);
    }

    public function show(Category $category): JsonResponse
    {
        return response()->json($category);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255|unique:categories,name,' . $category->id,
            'color' => 'nullable|string|max:16',
            'icon' => 'nullable|string|max:32',
            'is_violation' => 'boolean',
            'is_service' => 'boolean',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $category->update($data);

        return response()->json($category);
    }

    public function destroy(Request $request, Category $category): JsonResponse
    {
        $this->authorizeAdmin($request);

        if ($category->reportItems()->exists()) {
            return response()->json([
                'message' => 'Kategori tidak bisa dihapus karena masih dipakai laporan.',
            ], 422);
        }

        $category->delete();

        return response()->json(['message' => 'deleted']);
    }

    protected function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Hanya admin yang dapat mengubah kategori.');
    }
}
