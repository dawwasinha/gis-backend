<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    /**
     * Get all users.
     */
    public function getAllUsers()
    {
        return User::all();
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
}