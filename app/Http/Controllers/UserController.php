<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserService;
use App\Http\Requests\UserRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a listing of the users.
     */
    public function index()
    {
        return response()->json($this->userService->getAllUsers());
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(UserRequest $request)
    {
        Log::info($request->all());
        $user = $this->userService->createUser($request->validated());

        return response()->json($user, 201);
    }

    /**
     * Display the specified user.
     */
    public function show(int $id)
    {
        $user = $this->userService->getUserById($id);
        return response()->json($user);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UserRequest $request, User $user)
    {
        $user = $this->userService->updateUser($user, $request->validated());

        return response()->json($user);
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        $response = $this->userService->deleteUser($user);
        return response()->json($response);
    }
}
