<?php

namespace App\Http\Controllers;

use App\Exports\UsersExport;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Models\UserAnnouncement;
use Illuminate\Http\Request; 
use App\Services\UserService;
use App\Http\Requests\UserRequest;
use App\Models\Invoice;
use App\Models\Karya;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

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
    public function update(UpdateUserRequest $request, User $user)
    {
        $user = $this->userService->updateUser($user, $request->validated());

        return response()->json($user);
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        Karya::where('user_id', $user->id)->delete();
        Invoice::where('user_id', $user->id)->delete();

        $response = $this->userService->deleteUser($user);
        return response()->json($response);
    }

    public function byAuth()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Load user dengan relasi userAnnouncement
        $userWithAnnouncement = User::with('userAnnouncement')->find($user->id);
        
        // Prepare pengumuman response
        $pengumumanResponse = null;
        $userAnnouncement = $userWithAnnouncement->userAnnouncement;
        
        if ($userAnnouncement) {
            $pengumumanResponse = [
                'id' => $userAnnouncement->id,
                'status_lolos' => $userAnnouncement->status_lolos,
                'is_lolos' => $userAnnouncement->isLolos(),
                'is_tidak_lolos' => $userAnnouncement->isTidakLolos(),
                'kategori_lomba' => $userAnnouncement->kategori_lomba,
                'skor_akhir' => $userAnnouncement->skor_akhir,
                'ranking' => $userAnnouncement->ranking,
                'keterangan' => $userAnnouncement->keterangan,
                'tanggal_pengumuman' => $userAnnouncement->tanggal_pengumuman?->format('Y-m-d H:i:s'),
                'diumumkan_oleh' => $userAnnouncement->diumumkan_oleh,
                'created_at' => $userAnnouncement->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $userAnnouncement->updated_at?->format('Y-m-d H:i:s'),
            ];
        }

        return response()->json([
            'id' => $userWithAnnouncement->id,
            'name' => $userWithAnnouncement->name,
            'email' => $userWithAnnouncement->email,
            'role' => $userWithAnnouncement->role,
            'nisn' => $userWithAnnouncement->nisn,
            'nomor_wa' => $userWithAnnouncement->nomor_wa,
            'alamat' => $userWithAnnouncement->alamat,
            'provinsi_id' => $userWithAnnouncement->provinsi_id,
            'kabupaten_id' => $userWithAnnouncement->kabupaten_id,
            'jenis_lomba' => $userWithAnnouncement->jenis_lomba,
            'jenjang' => $userWithAnnouncement->jenjang,
            'kelas' => $userWithAnnouncement->kelas,
            'asal_sekolah' => $userWithAnnouncement->asal_sekolah,
            'link_twibbon' => $userWithAnnouncement->link_twibbon,
            'link_bukti_pembayaran' => $userWithAnnouncement->link_bukti_pembayaran,
            'status' => $userWithAnnouncement->status,
            'created_at' => $userWithAnnouncement->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $userWithAnnouncement->updated_at?->format('Y-m-d H:i:s'),
            'pengumuman' => $pengumumanResponse,
            'has_announcement' => $userAnnouncement !== null,
        ]);
    }

    public function participants(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'nisn' => 'nullable|string',
            'nomor_wa' => 'nullable|string',
            'alamat' => 'nullable|string',
            'provinsi_id' => 'required|integer',
            'kabupaten_id' => 'required|integer',
            'asal_sekolah' => 'nullable|string',
            'kelas' => 'nullable|string',
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
            'nisn' => $validated['nisn'],
            'nomor_wa' => $validated['nomor_wa'],
            'jenis_lomba' => $user->jenis_lomba,
            'jenjang' => $user->jenjang,
            'alamat' => $validated['alamat'],
            'provinsi_id' => $validated['provinsi_id'],
            'kabupaten_id' => $validated['kabupaten_id'],
            'asal_sekolah' => $validated['asal_sekolah'],
            'kelas' => $validated['kelas'] ?? $user->kelas,
            'guru' => $validated['guru'] ?? "",
            'wa_guru' => $validated['wa_guru'] ?? "",
            'email_guru' => $validated['email_guru'] ?? "",
            'link_twibbon' => $validated['link_twibbon'] ?? $user->link_twibbon,
            'status' => 'pending',
            'email_verified_at' => Carbon::now(),
        ]);

        return response()->json($user->fresh());
    }


    public function verifSuccess($id)
    {
        $user = User::where('id', $id)->first();
        if (!$user) {
            return response()->json(['error' => 'not found'], 404);
        }

        $user->update([
            'status' => 'success',
        ]);
        
        $invoice = Invoice::where('user_id', $id)->update([
            'status' => 'success'
        ]);

        return response()->json($user->fresh());
    }

    public function export(Request $request)
    {
        return Excel::download(new UsersExport, 'users.xlsx');
    }

    /**
     * Announce user status (lolos/tidak lolos)
     * 
     * @OA\Post(
     *     path="/api/users/{userId}/announce",
     *     summary="Mengumumkan status lolos user",
     *     tags={"Pengumuman"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status_lolos"},
     *             @OA\Property(property="status_lolos", type="string", enum={"lolos", "tidak_lolos"}),
     *             @OA\Property(property="kategori_lomba", type="string"),
     *             @OA\Property(property="skor_akhir", type="integer"),
     *             @OA\Property(property="ranking", type="integer"),
     *             @OA\Property(property="keterangan", type="string"),
     *             @OA\Property(property="tanggal_pengumuman", type="string", format="date")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Status berhasil diumumkan"),
     *     @OA\Response(response=422, description="Validasi gagal"),
     *     @OA\Response(response=404, description="User tidak ditemukan")
     * )
     */
    public function announceStatus(Request $request, $userId)
    {
        $request->validate([
            'status_lolos' => 'required|in:lolos,tidak_lolos',
            'kategori_lomba' => 'nullable|string|max:255',
            'skor_akhir' => 'nullable|integer',
            'ranking' => 'nullable|integer',
            'keterangan' => 'nullable|string',
            'tanggal_pengumuman' => 'nullable|date',
        ]);

        $user = User::findOrFail($userId);
        
        // Create or update announcement
        $announcement = UserAnnouncement::updateOrCreate(
            ['user_id' => $userId],
            [
                'status_lolos' => $request->status_lolos,
                'kategori_lomba' => $request->kategori_lomba ?? $user->jenis_lomba,
                'skor_akhir' => $request->skor_akhir,
                'ranking' => $request->ranking,
                'keterangan' => $request->keterangan,
                'tanggal_pengumuman' => $request->tanggal_pengumuman ?? now(),
                'diumumkan_oleh' => Auth::user()->name ?? 'System',
            ]
        );

        return response()->json([
            'message' => 'Status pengumuman berhasil disimpan',
            'data' => $announcement,
            'user' => $user
        ]);
    }

    /**
     * Get user announcement
     */
    public function getAnnouncement($userId)
    {
        $user = User::with('userAnnouncement')->findOrFail($userId);
        
        return response()->json([
            'user' => $user,
            'announcement' => $user->userAnnouncement,
            'has_announcement' => $user->hasAnnouncement()
        ]);
    }

    /**
     * Get all announcements
     */
    public function getAllAnnouncements(Request $request)
    {
        $query = UserAnnouncement::with('user');

        // Filter by status lolos
        if ($request->has('status_lolos')) {
            $query->where('status_lolos', $request->status_lolos);
        }

        // Filter by kategori lomba
        if ($request->has('kategori_lomba')) {
            $query->where('kategori_lomba', $request->kategori_lomba);
        }

        // Order by ranking if available
        if ($request->get('order_by') === 'ranking') {
            $query->orderByRanking();
        } else {
            $query->orderBy('tanggal_pengumuman', 'desc');
        }

        $announcements = $query->paginate($request->get('per_page', 15));

        return response()->json($announcements);
    }

    /**
     * Delete user announcement
     */
    public function deleteAnnouncement($userId)
    {
        $announcement = UserAnnouncement::where('user_id', $userId)->first();
        
        if (!$announcement) {
            return response()->json(['error' => 'Pengumuman tidak ditemukan'], 404);
        }

        $announcement->delete();

        return response()->json(['message' => 'Pengumuman berhasil dihapus']);
    }

}
