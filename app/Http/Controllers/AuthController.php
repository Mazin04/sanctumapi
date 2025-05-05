<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()->messages()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if ($user) {
            if ($user->google_id) {
                $user->update([
                    'name' => $request->name,
                    'password' => bcrypt($request->password),
                    'google_id' => null, // Clear google_id if switching to password-based login
                ]);
            } else {
                return response()->json(['message' => 'Email already registered'], 409);
            }
        } else {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['data' => $user, 'access_token' => $token, 'token_type' => 'Bearer']);
    }
    
    public function login(Request $request)
    {
        if (empty($request->email) || empty($request->password)) {
            return response()->json(['message' => 'unauthorized'], 401);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'passwordUserMismatch'], 401);
        }

        $user = User::where('email', $request['email'])->firstOrFail();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['data' => $user, 'access_token' => $token, 'token_type' => 'Bearer']);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->each(function ($token) {
            $token->delete();
        });
        // Invalidar la sesión y eliminar las cookies de autenticación
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Eliminar cookies relacionadas con Sanctum si las hay
        Cookie::queue(Cookie::forget('XSRF-TOKEN'));
        Cookie::queue(Cookie::forget('laravel_session'));
        
        return response()->json(['message' => 'logoutSuccess']);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function isEmailRegistered(Request $request)
    {
        $email = $request->input('email');
        $user = User::where('email', $email)->first();

        if ($user) {
            return response()->json(['registered' => true]);
        } else {
            return response()->json(['registered' => false]);
        }
    }
}
