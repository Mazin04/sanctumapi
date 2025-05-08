<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        $googleUser = Socialite::driver('google')->user();

        $user = User::where('email', $googleUser->email)->first();

        $localAvatarPath = null;
        if (!$user || !$user->avatar) {
            try {
                $avatarUrl = $googleUser->avatar;
                $avatarContents = Http::get($avatarUrl)->body();
                $filename = Str::uuid() . '.jpg';
                Storage::disk('public')->put("avatars/{$filename}", $avatarContents);
                $localAvatarPath = "storage/avatars/{$filename}";
            } catch (\Exception $e) {
                $localAvatarPath = null;
            }
        }

        if ($user) {
            $user->google_id = $googleUser->id;
            $user->avatar = $googleUser->avatar;
            $user->save();
        } else {
            $user = User::create([
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'password' => null,
                'google_id' => $googleUser->id,
                'avatar' => $localAvatarPath,
                'email_verified_at' => now(),
            ]);
        }

        if ($localAvatarPath) {
            logger()->info('Local avatar path: ' . $localAvatarPath);
        }
        Auth::login($user);
        return redirect(config('app.frontend_url') . "/home");
    }

    public function avatar()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        return response()->json([
            'avatar' => $user->avatar,
        ]);
    }
}
