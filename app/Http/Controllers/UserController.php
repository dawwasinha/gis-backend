<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request; 
use App\Services\UserService;
use App\Http\Requests\UserRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
    public function index(Request $request)
    {
        return response()->json($this->userService->getAllUsers($request));
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

    public function byAuth()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        return response()->json($user);
    }

    public function participants(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        Log::info($request->all());
        Log::info($user);
        // Validasi data
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'nisn' => 'nullable|string',
            'nomor_wa' => 'nullable|string',
            'alamat' => 'nullable|string',
            'provinsi_id' => 'required|integer',
            'kabupaten_id' => 'required|integer',
            'asal_sekolah' => 'nullable|string',
            'guru' => 'nullable|string',
            'wa_guru' => 'nullable|string',
            'email_guru' => 'nullable|email',
            'link_twibbon' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
        ]);

        // Simpan file twibbon jika ada
        if ($request->hasFile('link_twibbon')) {
            $file = $request->file('link_twibbon');
            $path = $file->store('twibbons', 'public'); // storage/app/public/twibbons
            $validated['link_twibbon'] = $path;
        }

        // Update user dengan data yang sudah tervalidasi
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'nisn' => $validated['nisn'] ?? null,
            'nomor_wa' => $validated['nomor_wa'] ?? null,
            'jenis_lomba' => $validated['jenis_lomba'] ?? $user->jenis_lomba,
            'jenjang' => $validated['jenjang'] ?? $user->jenjang,
            'alamat' => $validated['alamat'] ?? null,
            'provinsi_id' => $validated['provinsi_id'],
            'kabupaten_id' => $validated['kabupaten_id'],
            'asal_sekolah' => $validated['asal_sekolah'] ?? null,
            'guru' => $validated['guru'] ?? null,
            'wa_guru' => $validated['wa_guru'] ?? null,
            'email_guru' => $validated['email_guru'] ?? null,
            'link_twibbon' => $validated['link_twibbon'] ?? $user->link_twibbon,
            'status' => 'pending',
            'email_verified_at' => Carbon::now(),
        ]);

        return response()->json($user->fresh());
    }


}
