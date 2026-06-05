<?php

namespace App\Http\Controllers;

use App\Models\Lokasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LokasiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = Lokasi::query()->orderBy('sort_order')->orderBy('name');

        if ($request->boolean('active_only')) {
            $q->where('is_active', true);
        }
        if ($s = $request->string('search')->toString()) {
            $q->where('name', 'ilike', '%' . $s . '%');
        }

        $q->with(['lokets' => function ($w) use ($request) {
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
            'name' => 'required|string|max:255|unique:lokasis,name',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $lokasi = Lokasi::create($data);

        return response()->json($lokasi, 201);
    }

    public function update(Request $request, Lokasi $lokasi): JsonResponse
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255|unique:lokasis,name,' . $lokasi->id,
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $lokasi->update($data);

        return response()->json($lokasi);
    }

    public function destroy(Request $request, Lokasi $lokasi): JsonResponse
    {
        $this->authorizeAdmin($request);

        $lokasi->delete();

        return response()->json(['message' => 'deleted']);
    }

    protected function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Hanya admin yang dapat mengubah lokasi.');
    }
}
