<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['Неверный email или пароль.'],
            ]);
        }

        $user = Auth::user();
        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'role' => 'in:student,teacher',
            'grade' => 'nullable|integer|in:8,9',
            'referral_code' => 'nullable|string|exists:users,referral_code',
        ]);

        $referrer = null;
        if ($request->referral_code) {
            $referrer = User::where('referral_code', $request->referral_code)->first();
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'student',
            'grade' => $request->grade,
            'referred_by_user_id' => $referrer?->id,
            'trial_ends_at' => now()->addDays(3),
        ]);

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Выход выполнен']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load(['streak', 'skills.skill', 'badges.badge']);

        return response()->json([
            'user' => $user,
            'stats' => [
                'total_attempts' => $user->attempts()->count(),
                'correct_attempts' => $user->attempts()->where('is_correct', true)->count(),
                'current_streak' => $user->streak?->current_streak ?? 0,
                'badges_count' => $user->badges()->count(),
            ],
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'grade' => 'sometimes|integer|in:8,9',
            'school' => 'sometimes|string|max:255',
            'timezone' => 'sometimes|string|max:50',
        ]);

        $user->update($request->only(['name', 'grade', 'school', 'timezone']));

        return response()->json(['user' => $user]);
    }
}
