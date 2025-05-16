<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request; 
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UserService
{
    /**
     * Get all users.
     */
    public function getAllUsers(Request $request)
    {
        $query = User::query();

        if ($request->has('jenis_lomba')) {
            $query->where('jenis_lomba', $request->jenis_lomba);
        }

        if ($request->has('jenjang')) {
            $query->where('jenjang', $request->jenjang);
        }

        if ($request->has('status')) {
            // Cek jika status tidak null string (bukan "null" literal)
            if (strtolower($request->status) !== 'null') {
                $query->whereNotNull('status');
            } else {
                $query->whereNull('status');
            }
        }

        return $query->get();
    }

    /**
     * Create a new user.
     */
    public function createUser(array $data)
    {
        $data['password'] = Hash::make($data['password']);
        return User::create($data);
    }

    /**
     * Get a single user by ID.
     */
    public function getUserById(int $id)
    {
        return User::findOrFail($id);
    }

    /**
     * Update an existing user.
     */
    public function updateUser(User $user, array $data)
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);
        return $user;
    }

    /**
     * Delete a user.
     */
    public function deleteUser(User $user)
    {
        $user->delete();
        return ['message' => 'User deleted successfully'];
    }

    /**
     * Handle user participation.
     */
    public function participants(User $user, Request $request)
    {
        // Validasi termasuk file
        $validator = Validator::make($request->all(), [
            'jenis_lomba' => 'required|string',
            'jenjang' => 'required|string',
            'name' => 'required|string',
            'alamat' => 'nullable|string',
            'kelas' => 'nullable|string',
            'asalSekolah' => 'nullable|string',
            'email' => 'nullable|email',
            'guru' => 'nullable|string',
            'nisn' => 'nullable|string',
            'provinsi' => 'nullable|string',
            'status' => 'nullable|string',
            'link_twibbon' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048', // max 2MB
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $data = $request->only([
            'jenis_lomba',
            'jenjang',
            'nama',
            'alamat',
            'kelas',
            'asalSekolah',
            'email',
            'guru',
            'nisn',
            'provinsi',
            'status'
        ]);

        // Handle file upload
        if ($request->hasFile('link_twibbon')) {
            $path = $request->file('link_twibbon')->store('twibbons', 'public');
            $data['twibbon'] = $path;
        }

        $user->update($data);

        return $user->fresh();
    }
}