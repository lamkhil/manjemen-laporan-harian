<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nip' => 'required|string|max:32',
            'password' => 'required|string',
        ]);

        $nip = preg_replace('/\s+/', '', $data['nip']);

        $user = User::where('nip', $nip)->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'nip' => ['NIP/NIK atau password salah.'],
            ]);
        }

        $token = $user->createToken('spa-' . now()->timestamp)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->load(['bidang:id,name', 'defaultLokasi:id,name', 'defaultLoket:id,name']),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user()->load(['bidang:id,name', 'defaultLokasi:id,name', 'defaultLoket:id,name']));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'logout ok']);
    }
}
