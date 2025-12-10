<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        // 1. Cek Role Admin
        if ($request->user()->role !== 'admin') {
            return $this->errorResponse(
                'Akses ditolak. Hanya admin yang bisa melihat daftar user.',
                403,
                'FORBIDDEN'
            );
        }

        $users = User::all();
        return $this->successResponse($users, 'List semua user');
    }

    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->errorResponse('User tidak ditemukan.', 404, 'NOT_FOUND');
        }

        return $this->successResponse($user, 'Detail user ditemukan');
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->errorResponse('User tidak ditemukan.', 404, 'NOT_FOUND');
        }

        // Logic validasi role bisa ditambahkan di sini jika perlu
        // Contoh: Staff hanya bisa update profil sendiri

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'role' => 'in:admin,staff'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Input validasi gagal.', 400, 'INVALID_INPUT', $validator->errors()->all());
        }

        try {
            $user->update($request->only(['name', 'email', 'role']));
            return $this->successResponse($user, 'User berhasil diupdate');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengupdate user.', 500, 'SERVER_ERROR');
        }
    }

    public function destroy(Request $request, $id)
    {
        // 1. Cek Role Admin
        if ($request->user()->role !== 'admin') {
            return $this->errorResponse(
                'Akses ditolak. Hanya admin yang bisa menghapus user.',
                403,
                'FORBIDDEN'
            );
        }

        $user = User::find($id);

        if (!$user) {
            return $this->errorResponse('User tidak ditemukan.', 404, 'NOT_FOUND');
        }


        if ($user->id === $request->user()->id) {
            return $this->errorResponse(
                'Anda tidak bisa menghapus akun anda sendiri.',
                400,
                'INVALID_ACTION'
            );
        }

        $user->delete();

        return $this->successResponse(null, 'User berhasil dihapus', 200);
    }
}
