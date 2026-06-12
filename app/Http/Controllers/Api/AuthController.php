<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $rider = Rider::where('username', $request->username)->first();

        if (! $rider || ! Hash::check($request->password, $rider->password)) {
            throw ValidationException::withMessages([
                'username' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $rider->is_active) {
            return response()->json([
                'message' => 'Your account is inactive. Please contact admin.',
            ], 403);
        }

        // Delete old tokens
        $rider->tokens()->delete();

        // Create new token
        $token = $rider->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'rider' => [
                'id' => $rider->id,
                'username' => $rider->username,
                'name' => $rider->name,
                'phone' => $rider->phone,
                'email' => $rider->email,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    public function me(Request $request)
    {
        $rider = $request->user();

        return response()->json([
            'rider' => [
                'id' => $rider->id,
                'username' => $rider->username,
                'name' => $rider->name,
                'phone' => $rider->phone,
                'email' => $rider->email,
                'is_active' => $rider->is_active,
            ],
        ]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $rider = $request->user();

        // Verify current password
        if (!Hash::check($request->current_password, $rider->password)) {
            return response()->json([
                'message' => 'Current password is incorrect',
                'errors' => [
                    'current_password' => ['The current password is incorrect.']
                ]
            ], 422);
        }

        // Update password
        $rider->password = Hash::make($request->new_password);
        $rider->save();

        return response()->json([
            'message' => 'Password changed successfully',
        ]);
    }
}
