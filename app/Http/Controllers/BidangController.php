<?php

namespace App\Http\Controllers;

use App\Models\Bidang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BidangController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = Bidang::query()->orderBy('sort_order')->orderBy('name');

        if ($request->boolean('active_only')) {
            $q->where('is_active', true);
        }
        if ($s = $request->string('search')->toString()) {
            $q->where('name', 'ilike', '%' . $s . '%');
        }

        $q->with(['jenisLayanans' => function ($w) use ($request) {
            if ($request->boolean('active_only')) {
                $w->where('is_active', true);
            }
        }]);

        return response()->json($q->get());
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'name' => 'required|string|max:255|unique:bidangs,name',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $bidang = Bidang::create($data);

        return response()->json($bidang, 201);
    }

    public function update(Request $request, Bidang $bidang): JsonResponse
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255|unique:bidangs,name,' . $bidang->id,
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $bidang->update($data);

        return response()->json($bidang);
    }

    public function destroy(Request $request, Bidang $bidang): JsonResponse
    {
        $this->authorizeAdmin($request);

        $bidang->delete();

        return response()->json(['message' => 'deleted']);
    }

    protected function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Hanya admin yang dapat mengubah bidang.');
    }
}
