<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\StaffPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class StaffAuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! $user->is_active || ! Hash::check($validated['password'], $user->password)) {
            AuditLog::record(
                $user, // null if the email doesn't exist at all — still logged, just with no actor
                'staff.login_failed',
                "Failed login attempt for {$validated['email']}" . ($user && ! $user->is_active ? ' (account inactive)' : ''),
                'User',
                $user?->id,
                ['email' => $validated['email'], 'ip' => $request->ip()],
            );

            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        $token = $user->createToken('staff')->plainTextToken;

        AuditLog::record(
            $user,
            'staff.login',
            "{$user->name} logged in",
            'User',
            $user->id,
            ['ip' => $request->ip()],
        );

        return response()->json([
            'token' => $token,
            'user' => StaffPermissions::payload($user),
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => StaffPermissions::payload($request->user()),
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $user->currentAccessToken()?->delete();

        AuditLog::record($user, 'staff.logout', "{$user->name} logged out", 'User', $user->id);

        return response()->json(['message' => 'Logged out.']);
    }
}
