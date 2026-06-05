<?php

namespace App\Http\Controllers;

use App\Models\JenisLayanan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JenisLayananController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = JenisLayanan::query()->with('bidang:id,name')->orderBy('sort_order')->orderBy('name');

        if ($request->boolean('active_only')) {
            $q->where('is_active', true);
        }
        if ($bid = $request->integer('bidang_id')) {
            $q->where('bidang_id', $bid);
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
            'bidang_id' => 'required|exists:bidangs,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $row = JenisLayanan::create($data);

        return response()->json($row->load('bidang:id,name'), 201);
    }

    public function update(Request $request, JenisLayanan $jenisLayanan): JsonResponse
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'bidang_id' => 'sometimes|exists:bidangs,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $jenisLayanan->update($data);

        return response()->json($jenisLayanan->load('bidang:id,name'));
    }

    public function destroy(Request $request, JenisLayanan $jenisLayanan): JsonResponse
    {
        $this->authorizeAdmin($request);

        $jenisLayanan->delete();

        return response()->json(['message' => 'deleted']);
    }

    protected function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Hanya admin yang dapat mengubah jenis layanan.');
    }
}
