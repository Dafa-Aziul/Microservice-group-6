<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    use ApiResponse;

    // 1. LIST SEMUA USER (Hanya Admin)
    public function index(Request $request)
    {
        Log::info('Request: Ambil semua user');

        if ($request->user()->role !== 'admin') {
            Log::warning('Akses ditolak saat mengambil daftar user', [
                'requester_id' => $request->user()->id,
                'role' => $request->user()->role
            ]);

            return $this->errorResponse(
                'Akses ditolak. Hanya admin yang bisa melihat daftar user.',
                403,
                'FORBIDDEN'
            );
        }

        $users = User::all();

        Log::info('Response: Daftar user berhasil diambil', [
            'count' => $users->count()
        ]);

        return $this->successResponse($users, 'List semua user');
    }

    // 2. DETAIL USER
    public function show($id)
    {
        Log::info('Request: Ambil detail user', [
            'user_id' => $id
        ]);

        $user = User::find($id);

        if (!$user) {
            Log::warning('User tidak ditemukan', [
                'user_id' => $id
            ]);

            return $this->errorResponse('User tidak ditemukan.', 404, 'NOT_FOUND');
        }

        Log::info('Detail user berhasil diambil', [
            'user_id' => $id
        ]);

        return $this->successResponse($user, 'Detail user ditemukan');
    }

    // 3. UPDATE USER
    public function update(Request $request, $id)
    {
        Log::info('Request: Update user', [
            'user_id' => $id,
            'payload' => $request->all()
        ]);

        $user = User::find($id);

        if (!$user) {
            Log::warning('User tidak ditemukan saat update', [
                'user_id' => $id
            ]);

            return $this->errorResponse('User tidak ditemukan.', 404, 'NOT_FOUND');
        }

        $validator = Validator::make($request->all(), [
            'name'  => 'sometimes|string',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'role'  => 'in:admin,staff'
        ]);

        if ($validator->fails()) {
            Log::warning('Validasi gagal saat update user', [
                'user_id' => $id,
                'errors'  => $validator->errors()
            ]);

            return $this->errorResponse(
                'Input validasi gagal.',
                400,
                'INVALID_INPUT',
                $validator->errors()->all()
            );
        }

        try {
            $user->update($request->only(['name', 'email', 'role']));

            Log::info('User berhasil diupdate', [
                'user_id' => $id
            ]);

            return $this->successResponse($user, 'User berhasil diupdate');

        } catch (\Exception $e) {
            Log::error('Error: Gagal mengupdate user', [
                'user_id' => $id,
                'error'   => $e->getMessage()
            ]);

            return $this->errorResponse('Gagal mengupdate user.', 500, 'SERVER_ERROR');
        }
    }

    // 4. HAPUS USER
    public function destroy(Request $request, $id)
    {
        Log::info('Request: Hapus user', [
            'target_id'    => $id,
            'requester_id' => $request->user()->id
        ]);

        if ($request->user()->role !== 'admin') {
            Log::warning('Akses ditolak saat menghapus user', [
                'requester_id' => $request->user()->id,
                'role'         => $request->user()->role
            ]);

            return $this->errorResponse(
                'Akses ditolak. Hanya admin yang bisa menghapus user.',
                403,
                'FORBIDDEN'
            );
        }

        $user = User::find($id);

        if (!$user) {
            Log::warning('User tidak ditemukan saat delete', [
                'user_id' => $id
            ]);

            return $this->errorResponse('User tidak ditemukan.', 404, 'NOT_FOUND');
        }

        if ($user->id === $request->user()->id) {
            Log::warning('Admin mencoba menghapus akun sendiri', [
                'user_id' => $id
            ]);

            return $this->errorResponse(
                'Anda tidak bisa menghapus akun anda sendiri.',
                400,
                'INVALID_ACTION'
            );
        }

        try {
            $user->delete();

            Log::info('User berhasil dihapus', [
                'user_id' => $id
            ]);

            return $this->successResponse(null, 'User berhasil dihapus', 200);

        } catch (\Throwable $th) {
            Log::error('Error: Gagal menghapus user', [
                'user_id' => $id,
                'error'   => $th->getMessage()
            ]);

            return $this->errorResponse('Gagal menghapus user.', 500, 'SERVER_ERROR');
        }
    }
}
