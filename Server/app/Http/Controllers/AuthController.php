<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Error;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class AuthController
{
    private $faceAuthServiceUrl = 'http://localhost:5000';

    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        if (User::where('email', $request->email)->first()) {
            return response()->json(['status' => 405, 'message' => 'Email Has Been Taken'], 405);
        }

        $user = User::create($validated);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 200,
            'user' => $user,
            'token' => $token,
            'message' => 'Registration successful. You can now register your face for facial login.'
        ], 200);
    }

    public function login(LoginRequest $request)
    {
        try {
            $validated = $request->validated();

            // NEW: Check if it's a face login attempt
            if ($request->has('face_login') && $request->face_login === true) {
                return $this->faceLogin($request);
            }

            // Existing email/password login
            if (!Auth::attempt($validated)) {
                return response()->json(['status' => 401, 'message' => 'Invalid credentials'], 401);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json(['status' => 404, 'message' => 'User not found'], 404);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            // NEW: Check if user has face registered
            $faceRegistered = $this->checkFaceRegistered(new Request(['user_id' => $user->id]));

            return response()->json([
                'role' => $user->role,
                'token' => $token,
                'message' => 'Logged in.',
                'face_registered' => $faceRegistered  // NEW: Return face status
            ], 200);
        } catch (Error $e) {
            return response()->json(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    private function faceLogin(Request $request)
    {
        try {
            // Call Python face recognition service
            $response = Http::post($this->faceAuthServiceUrl . '/verify-face', [
                'image' => $request->image_data
            ]);

            $result = $response->json();

            if ($result['success']) {
                $user = User::find($result['user_id']);

                if (!$user) {
                    return response()->json(['status' => 404, 'message' => 'User not found'], 404);
                }

                Auth::login($user);
                $token = $user->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'role' => $user->role,
                    'token' => $token,
                    'message' => 'Face recognition login successful.',
                    'face_registered' => true
                ], 200);
            }

            return response()->json([
                'status' => 401,
                'message' => $result['message'] ?? 'Face recognition failed'
            ], 401);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Face recognition service unavailable: ' . $e->getMessage()
            ], 500);
        }
    }

    // NEW: Register Face Method
    public function registerFace(Request $request)
    {
        try {

            $user = Auth::user();

            if (!$user) {
                return response()->json(['status' => 401, 'message' => 'Unauthorized'], 401);
            }

            $response = Http::post($this->faceAuthServiceUrl . '/register-face', [
                'user_id' => $user->id,
                'image' => $request->image_data
            ]);

            $result = $response->json();

            if ($result['success']) {
                return response()->json([
                    'status' => 200,
                    'message' => 'Face registered successfully'
                ], 200);
            }

            return response()->json([
                'status' => 400,
                'message' => $result['message'] ?? 'Face registration failed'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Face registration service unavailable: ' . $e->getMessage()
            ], 500);
        }
    }

    // NEW: Check if face is registered
    public function checkFaceRegistered(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['registered' => false]);
            }

            $response = Http::get($this->faceAuthServiceUrl . '/check-face-registered/' . $user->id);
            $result = $response->json();

            return response()->json(['registered' => $result['registered'] ?? false]);

        } catch (\Exception $e) {
            return response()->json(['registered' => false]);
        }
    }

    // Existing methods remain the same
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['status' => 200, 'message' => 'Logged out.'], 200);
    }

    public function reset_password(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email',
                'password' => 'required|min:7',
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json(['status' => 404, 'message' => 'User not found'], 404);
            }

            $user->update([
                'password' => bcrypt($request->password),
            ]);

            return response()->json(['status' => 200, 'message' => 'Password reset successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }
}
