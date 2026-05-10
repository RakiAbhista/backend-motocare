<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendOtpMail;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * 1. REGISTRASI
     */
    public function register(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::create([
            'full_name' => $request->full_name,
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'Customer', // Otomatis menjadi Customer
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Registrasi berhasil.',
            'access_token' => $token,
            'user' => $user
        ], 201);
    }

    /**
     * 2. LOGIN
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        // Cek user dan password
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email atau password salah.'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil.',
            'access_token' => $token,
            'user' => $user
        ], 200);
    }

    /**
     * 3. FORGOT PASSWORD (Kirim OTP)
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email tidak terdaftar.'
            ], 404);
        }

        // Generate 6 digit angka random
        $otp = (string) rand(111111, 999999);
        
        // Simpan OTP dan masa berlaku (5 menit dari sekarang)
        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(5)
        ]);

        // Kirim Email OTP
        Mail::to($user->email)->send(new SendOtpMail($otp));

        return response()->json([
            'status' => 'success',
            'message' => 'Kode OTP 6 digit telah dikirim ke email kamu.'
        ], 200);
    }

    /**
     * 4. VERIFIKASI OTP (Cek OTP sebelum masuk halaman reset password)
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        $user = User::where('email', $request->email)
                    ->where('otp_code', $request->otp)
                    ->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kode OTP salah.'
            ], 400);
        }

        // Cek apakah OTP sudah kadaluarsa
        if (Carbon::now()->isAfter($user->otp_expires_at)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kode OTP sudah kadaluarsa. Silakan minta ulang.'
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'OTP valid. Silakan masukkan password baru.'
        ], 200);
    }

    /**
     * 5. RESET PASSWORD (Simpan password baru)
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
            'password' => 'required|min:8|confirmed'
        ]);

        // Validasi ulang OTP untuk keamanan ganda sebelum mengubah password
        $user = User::where('email', $request->email)
                    ->where('otp_code', $request->otp)
                    ->where('otp_expires_at', '>', Carbon::now())
                    ->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sesi tidak valid, kode OTP salah atau sudah kadaluarsa.'
            ], 400);
        }

        // Ubah password dan bersihkan data OTP
        $user->update([
            'password' => Hash::make($request->password),
            'otp_code' => null,
            'otp_expires_at' => null
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Password berhasil diubah! Silakan login menggunakan password baru.'
        ], 200);
    }

    /**
     * 6. LOGOUT
     */
    public function logout(Request $request)
    {
        // Hapus token yang sedang digunakan
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil.'
        ], 200);
    }
}