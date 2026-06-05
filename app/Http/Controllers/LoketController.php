<?php

namespace App\Http\Controllers;

use App\Models\Loket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = Loket::query()->with('lokasi:id,name')->orderBy('sort_order')->orderBy('name');

        if ($request->boolean('active_only')) {
            $q->where('is_active', true);
        }
        if ($lokasiId = $request->integer('lokasi_id')) {
            $q->where('lokasi_id', $lokasiId);
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
            'name' => 'required|string|max:255',
            'lokasi_id' => 'nullable|exists:lokasis,id',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $loket = Loket::create($data);

        return response()->json($loket->load('lokasi:id,name'), 201);
    }

    public function update(Request $request, Loket $loket): JsonResponse
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'lokasi_id' => 'nullable|exists:lokasis,id',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $loket->update($data);

        return response()->json($loket->load('lokasi:id,name'));
    }

    public function destroy(Request $request, Loket $loket): JsonResponse
    {
        $this->authorizeAdmin($request);

        $loket->delete();

        return response()->json(['message' => 'deleted']);
    }

    protected function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Hanya admin yang dapat mengubah loket.');
    }
}
