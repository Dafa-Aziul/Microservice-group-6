<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use ApiResponse;

    // 1. REGISTER USER
    public function register(Request $request)
    {
        Log::info("Request: Registrasi user baru", [
            "email" => $request->email
        ]);

        try {
            // VALIDASI INPUT
            $validator = Validator::make($request->all(), [
                'name'     => 'required|string',
                'email'    => 'required|email|unique:users',
                'password' => 'required|min:6',
                'role'     => 'in:admin,staff'
            ]);

            if ($validator->fails()) {
                Log::warning("Validasi registrasi gagal", [
                    "errors" => $validator->errors()->all()
                ]);

                return $this->errorResponse(
                    'Input validasi gagal.',
                    400,
                    'INVALID_INPUT',
                    $validator->errors()->all()
                );
            }

            // CREATE USER
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'role'     => $request->role ?? 'staff'
            ]);

            Log::info("Registrasi user berhasil", [
                "user_id" => $user->id,
                "email"   => $user->email
            ]);

            return $this->successResponse($user, 'Registrasi berhasil', 201);

        } catch (\Throwable $e) {
            Log::error("Error: Registrasi user gagal", [
                "error" => $e->getMessage(),
                "email" => $request->email
            ]);

            return $this->errorResponse(
                'Terjadi kesalahan server.',
                500,
                'SERVER_ERROR'
            );
        }
    }

    // 2. LOGIN USER
    public function login(Request $request)
    {
        Log::info("Request: Login user", [
            "email" => $request->email
        ]);

        try {
            // VALIDASI INPUT
            $validator = Validator::make($request->all(), [
                "email"    => "required|email",
                "password" => "required"
            ]);

            if ($validator->fails()) {
                Log::warning("Validasi login gagal", [
                    "errors" => $validator->errors()->all()
                ]);

                return $this->errorResponse(
                    'Input tidak valid.',
                    400,
                    'INVALID_INPUT',
                    $validator->errors()->all()
                );
            }

            // CEK USER
            $user = User::where("email", $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                Log::warning("Login gagal: email atau password salah", [
                    "email" => $request->email
                ]);

                return $this->errorResponse(
                    'Email atau password salah.',
                    401,
                    'AUTH_FAILED'
                );
            }

            // BUAT TOKEN
            $token = $user->createToken("api-token")->plainTextToken;

            Log::info("Login user berhasil", [
                "user_id" => $user->id,
                "email"   => $user->email
            ]);

            return $this->successResponse([
                "token" => $token,
                "user"  => $user
            ], 'Login berhasil');

        } catch (\Throwable $e) {
            Log::error("Error: Terjadi kesalahan saat login", [
                "error" => $e->getMessage(),
                "email" => $request->email
            ]);

            return $this->errorResponse(
                'Terjadi kesalahan server.',
                500,
                'SERVER_ERROR'
            );
        }
    }

    // 3. LOGOUT
    public function logout(Request $request)
    {
        Log::info("Request: Logout user", [
            "user_id" => $request->user()->id
        ]);

        try {
            $request->user()->currentAccessToken()->delete();

            Log::info("Logout berhasil", [
                "user_id" => $request->user()->id
            ]);

            return $this->successResponse(null, 'Logout berhasil');

        } catch (\Throwable $e) {
            Log::error("Error: Logout gagal", [
                "error"   => $e->getMessage(),
                "user_id" => $request->user()->id
            ]);

            return $this->errorResponse(
                'Terjadi kesalahan server.',
                500,
                'SERVER_ERROR'
            );
        }
    }

    // 4. ME — GET USER DETAILS
    public function me(Request $request)
    {
        Log::info("Request: Ambil data user (me)", [
            "user_id" => $request->user()->id
        ]);

        try {
            $user = $request->user();

            Log::info("Data user (me) berhasil diambil", [
                "user_id" => $user->id
            ]);

            return $this->successResponse($user, 'Data user berhasil diambil');

        } catch (\Throwable $e) {
            Log::error("Error mengambil data user (me)", [
                "error"   => $e->getMessage(),
                "user_id" => $request->user()->id
            ]);

            return $this->errorResponse(
                'Terjadi kesalahan server.',
                500,
                'SERVER_ERROR'
            );
        }
    }
}
