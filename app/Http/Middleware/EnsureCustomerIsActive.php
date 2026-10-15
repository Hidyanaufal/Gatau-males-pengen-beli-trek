<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menolak token milik akun yang dinonaktifkan atau bukan pelanggan,
 * walaupun token tersebut masih tercatat.
 */
class EnsureCustomerIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active || $user->role !== UserRole::Customer->value) {
            return ApiResponse::error('Akun tidak aktif atau tidak memiliki akses.', 403);
        }

        return $next($request);
    }
}
