<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        // role dan is_active tidak bisa diisi dari request (default: customer, aktif).
        $user = User::create($request->safe()->only(['name', 'email', 'password', 'phone', 'address']));

        $token = $user->createToken($request->input('device_name', 'android'))->plainTextToken;

        return ApiResponse::success([
            'user' => (new UserResource($user))->resolve(),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Pendaftaran berhasil.', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->input('email'))->first();

        // Pesan sama untuk email salah / password salah / bukan pelanggan,
        // supaya tidak membocorkan akun mana yang ada.
        if (
            ! $user
            || ! Hash::check($request->input('password'), $user->password)
            || $user->role !== UserRole::Customer->value
        ) {
            return ApiResponse::error('Email atau password salah.', 401);
        }

        if (! $user->is_active) {
            return ApiResponse::error('Akun Anda dinonaktifkan. Hubungi pihak laundry.', 403);
        }

        $token = $user->createToken($request->input('device_name', 'android'))->plainTextToken;

        return ApiResponse::success([
            'user' => (new UserResource($user))->resolve(),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Login berhasil.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Logout berhasil.');
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::resource(new UserResource($request->user()), 'Profil pelanggan.');
    }
}
