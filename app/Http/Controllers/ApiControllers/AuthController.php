<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Handle user registration.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * Send password reset link.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email tidak terdaftar'
            ], 404);
        }

        // Generate token manually or via Password broker
        // For API, we often just create a token entry
        $token = \Illuminate\Support\Str::random(64);
        
        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => $token, 'created_at' => now()]
        );

        // In a real app, you'd send an email here.
        // For this project, we'll return the token in the response for debugging/demo purposes 
        // if app_env is local, otherwise just say "Check email"
        
        $debugInfo = [];
        if (config('app.env') === 'local') {
            $debugInfo['reset_url'] = "http://localhost:5173/reset-password?token=$token&email=" . urlencode($request->email);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tautan pemulihan kata sandi telah dikirim ke email Anda.',
            ...$debugInfo
        ]);
    }

    /**
     * Reset password using token.
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $record = \Illuminate\Support\Facades\DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$record || \Carbon\Carbon::parse($record->created_at)->addHours(2)->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid atau sudah kadaluarsa.'
            ], 400);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) return response()->json(['success' => false, 'message' => 'User tidak ditemukan.'], 404);

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // Delete token
        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kata sandi berhasil diperbarui. Silakan masuk kembali.'
        ]);
    }

    /**
     * Handle user login.
     */
    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah'
            ], 401);
        }

        $user = User::with(['person.assignments.structure', 'person.assignments.role'])->where('email', $request['email'])->firstOrFail();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    /**
     * Handle user logout.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil'
        ]);
    }

    /**
     * Get authenticated user details.
     */
    public function user(Request $request)
    {
        $user = $request->user()->load(['person.assignments.structure', 'person.assignments.role']);

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Redirect to Google for authentication.
     */
    public function redirectToGoogle()
    {
        return response()->json([
            'url' => Socialite::driver('google')->stateless()->redirect()->getTargetUrl()
        ]);
    }

    /**
     * Handle internal Google callback.
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // Create user if doesn't exist
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'password' => Hash::make(Str::random(24)),
                ]);
            } else {
                // Update google_id and avatar
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');

            return redirect()->away($frontendUrl . "/login/callback?token=$token");
        } catch (\Exception $e) {
            Log::error('Google Auth Error: ' . $e->getMessage());
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
            return redirect()->away($frontendUrl . "/login?error=google_auth_failed");
        }
    }
}
