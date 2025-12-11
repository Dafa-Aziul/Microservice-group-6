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

    // =========================
    // 1. REGISTER USER
    // =========================
    public function register(Request $request)
    {
        $cid = $request->attributes->get('correlation_id');

        Log::info("REQUEST REGISTER USER", [
            "email" => $request->email,
            "correlation_id" => $cid
        ]);

        try {
            $validator = Validator::make($request->all(), [
                'name'     => 'required|string',
                'email'    => 'required|email|unique:users',
                'password' => 'required|min:6',
                'role'     => 'in:admin,staff'
            ]);

            if ($validator->fails()) {

                Log::warning("REGISTER FAILED: VALIDATION ERROR", [
                    "errors" => $validator->errors()->all(),
                    "correlation_id" => $cid
                ]);

                return $this->errorResponse(
                    'Input validasi gagal.',
                    400,
                    'INVALID_INPUT',
                    $validator->errors()->all()
                );
            }

            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'role'     => $request->role ?? 'staff'
            ]);

            Log::info("REGISTER SUCCESS", [
                "user_id" => $user->id,
                "email" => $user->email,
                "correlation_id" => $cid
            ]);

            return $this->successResponse($user, 'Registrasi berhasil', 201);

        } catch (\Throwable $e) {

            Log::error("REGISTER ERROR", [
                "error" => $e->getMessage(),
                "correlation_id" => $cid
            ]);

            return $this->errorResponse(
                'Terjadi kesalahan server.',
                500,
                'SERVER_ERROR'
            );
        }
    }

    // =========================
    // 2. LOGIN USER
    // =========================
    public function login(Request $request)
    {
        $cid = $request->attributes->get('correlation_id');

        Log::info("REQUEST LOGIN USER", [
            "email" => $request->email,
            "correlation_id" => $cid
        ]);

        try {
            $validator = Validator::make($request->all(), [
                "email"    => "required|email",
                "password" => "required"
            ]);

            if ($validator->fails()) {

                Log::warning("LOGIN FAILED: VALIDATION ERROR", [
                    "errors" => $validator->errors()->all(),
                    "correlation_id" => $cid
                ]);

                return $this->errorResponse(
                    'Input tidak valid.',
                    400,
                    'INVALID_INPUT',
                    $validator->errors()->all()
                );
            }

            $user = User::where("email", $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {

                Log::warning("LOGIN FAILED: WRONG CREDENTIALS", [
                    "email" => $request->email,
                    "correlation_id" => $cid
                ]);

                return $this->errorResponse(
                    'Email atau password salah.',
                    401,
                    'AUTH_FAILED'
                );
            }

            $token = $user->createToken("api-token")->plainTextToken;

            Log::info("LOGIN SUCCESS", [
                "user_id" => $user->id,
                "email" => $user->email,
                "correlation_id" => $cid
            ]);

            return $this->successResponse([
                "token" => $token,
                "user"  => $user
            ], 'Login berhasil');

        } catch (\Throwable $e) {

            Log::error("LOGIN ERROR", [
                "error" => $e->getMessage(),
                "correlation_id" => $cid
            ]);

            return $this->errorResponse(
                'Terjadi kesalahan server.',
                500,
                'SERVER_ERROR'
            );
        }
    }

    // =========================
    // 3. LOGOUT
    // =========================
    public function logout(Request $request)
    {
        $cid = $request->attributes->get('correlation_id');

        Log::info("REQUEST LOGOUT USER", [
            "user_id" => $request->user()->id,
            "correlation_id" => $cid
        ]);

        try {
            $request->user()->currentAccessToken()->delete();

            Log::info("LOGOUT SUCCESS", [
                "user_id" => $request->user()->id,
                "correlation_id" => $cid
            ]);

            return $this->successResponse(null, 'Logout berhasil');

        } catch (\Throwable $e) {

            Log::error("LOGOUT ERROR", [
                "error" => $e->getMessage(),
                "correlation_id" => $cid
            ]);

            return $this->errorResponse(
                'Terjadi kesalahan server.',
                500,
                'SERVER_ERROR'
            );
        }
    }

    // =========================
    // 4. ME
    // =========================
    public function me(Request $request)
    {
        $cid = $request->attributes->get('correlation_id');

        Log::info("REQUEST ME", [
            "user_id" => $request->user()->id ?? null,
            "correlation_id" => $cid
        ]);

        try {

            $user = $request->user();

            Log::info("ME SUCCESS", [
                "user_id" => $user->id,
                "correlation_id" => $cid
            ]);

            return $this->successResponse($user, 'Data user berhasil diambil');

        } catch (\Throwable $e) {

            Log::error("ME ERROR", [
                "error" => $e->getMessage(),
                "correlation_id" => $cid
            ]);

            return $this->errorResponse(
                'Terjadi kesalahan server.',
                500,
                'SERVER_ERROR'
            );
        }
    }

    // =========================
    // 5. TOKEN VALIDATION UNTUK MICROSERVICE
    // =========================
    public function validateToken(Request $request)
    {
        $cid = $request->attributes->get('correlation_id');

        Log::info("REQUEST TOKEN VALIDATION", [
            "correlation_id" => $cid
        ]);

        try {
            $token = $request->bearerToken();

            if (!$token) {
                Log::warning("TOKEN VALIDATION FAILED: NO TOKEN", [
                    "correlation_id" => $cid
                ]);
                return $this->errorResponse("Token tidak ditemukan.", 401, "TOKEN_NOT_FOUND");
            }

            $pat = \Laravel\Sanctum\PersonalAccessToken::findToken($token);

            if (!$pat) {
                Log::warning("TOKEN VALIDATION FAILED: INVALID TOKEN", [
                    "correlation_id" => $cid
                ]);
                return $this->errorResponse("Token tidak valid.", 401, "TOKEN_INVALID");
            }

            $user = $pat->tokenable;

            Log::info("TOKEN VALIDATION SUCCESS", [
                "user_id" => $user->id,
                "role" => $user->role,
                "correlation_id" => $cid
            ]);

            return $this->successResponse($user, "Token valid");

        } catch (\Throwable $e) {

            Log::error("TOKEN VALIDATION ERROR", [
                "error" => $e->getMessage(),
                "correlation_id" => $cid
            ]);

            return $this->errorResponse(
                'Terjadi kesalahan server.',
                500,
                'SERVER_ERROR'
            );
        }
    }
}
