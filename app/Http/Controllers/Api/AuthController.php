<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('name', $request->username)
                    ->orWhere('email', $request->username)
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Username atau password salah!'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Route ini di luar middleware auth:sanctum (belum ada token pas request masuk),
        // jadi $request->user() masih kosong. Kasih tau manual biar ActivityLogger bisa nyatet siapa yang login.
        $request->setUserResolver(fn () => $user);

        ActivityLogger::log(
            $request, 'login', 'User', $user->id,
            "{$user->name} ({$user->role}) login ke sistem"
        );

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'   => $user->id,
                'name' => $user->name,
                'role' => $user->role ?? 'user',
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        ActivityLogger::log(
            $request, 'logout', 'User', $user->id,
            "{$user->name} logout dari sistem"
        );

        $user->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        return response()->json([
            'id'   => $request->user()->id,
            'name' => $request->user()->name,
            'role' => $request->user()->role ?? 'user',
        ]);
    }
}