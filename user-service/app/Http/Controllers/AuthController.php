<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'in:admin,staff'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Input validasi gagal.',
                400,
                'INVALID_INPUT',
                $validator->errors()->all()
            );
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role ?? 'staff'
            ]);

            return $this->successResponse($user, 'Registrasi berhasil', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Terjadi kesalahan server.', 500, 'SERVER_ERROR');
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "email" => "required|email",
            "password" => "required"
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Input tidak valid.', 400, 'INVALID_INPUT', $validator->errors()->all());
        }

        $user = User::where("email", $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->errorResponse('Email atau password salah.', 401, 'AUTH_FAILED');
        }

        $token = $user->createToken("api-token")->plainTextToken;

        return $this->successResponse([
            'token' => $token,
            'user' => $user
        ], 'Login berhasil');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->successResponse(null, 'Logout berhasil');
    }

    public function me(Request $request)
    {
        return $this->successResponse($request->user(), 'Data user berhasil diambil');
    }
}
