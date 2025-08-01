<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Mail\ResetPasswordCodeMail;
use App\Models\PasswordReset;
use Illuminate\Http\Request;
use App\Models\User;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

/**
 * @OA\Tag(
 *     name="Auth",
 *     description="Autentikasi dan manajemen akun pengguna"
 * )
 */
class AuthController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @OA\Post(
     *     path="/api/register",
     *     summary="Register akun baru",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UserRequest")
     *     ),
     *     @OA\Response(response=201, description="User terdaftar"),
     *     @OA\Response(response=422, description="Validasi gagal")
     * )
     */
    public function register(UserRequest $request)
    {
        try {
            Log::info('Registering user', ['request' => $request->all()]);

            $user = $this->userService->createUser($request->validated());
            $token = JWTAuth::fromUser($user);

            return response()->json([
                'token' => $token,
                'user' => $user,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Login dengan email & password",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="siti@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="rahasia123")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Berhasil login dan mendapatkan token"),
     *     @OA\Response(response=401, description="Kredensial salah")
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');

        Log::info('Login attempt', ['credentials' => $credentials]);

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Invalid credentials'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }

        return response()->json([
            'token' => $token,
            'role' => Auth::user()->role,
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => Auth::user(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     summary="Logout dan invalidasi token",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Berhasil logout"),
     *     @OA\Response(response=500, description="Gagal logout")
     * )
     */
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (JWTException $e) {
            return response()->json(['error' => 'Failed to logout, please try again'], 500);
        }

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * @OA\Post(
     *     path="/api/forgot-password",
     *     summary="Kirim kode reset password ke email",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Kode dikirim ke email"),
     *     @OA\Response(response=422, description="Validasi gagal")
     * )
     */
    public function sendResetCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $token = rand(1000, 9999); // kode 4 digit

        PasswordReset::updateOrCreate(
            ['email' => $request->email],
            ['token' => $token, 'created_at' => now()]
        );

        Mail::to($request->email)->send(new ResetPasswordCodeMail($token));

        Log::info('Kirim kode reset password', ['email' => $request->email, 'token' => $token]);

        return response()->json([
            'message' => 'Kode verifikasi dikirim ke email.',
            'token' => $token,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/reset-password",
     *     summary="Reset password dengan token yang dikirim",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","token","password","password_confirmation"},
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="password", type="string", format="password"),
     *             @OA\Property(property="password_confirmation", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Password berhasil direset"),
     *     @OA\Response(response=400, description="Token tidak valid atau kadaluarsa"),
     *     @OA\Response(response=422, description="Validasi gagal")
     * )
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'token' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $reset = PasswordReset::where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$reset || Carbon::parse($reset->created_at)->addMinutes(15)->isPast()) {
            return response()->json(['error' => 'Token tidak valid atau sudah kedaluwarsa.'], 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->password = bcrypt($request->password);
        $user->save();

        PasswordReset::where('email', $request->email)->delete();

        return response()->json(['message' => 'Password berhasil direset.']);
    }

    /**
     * @OA\Get(
     *     path="/api/users/byAuth",
     *     summary="Ambil data user yang sedang login",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Profil user"),
     *     @OA\Response(response=404, description="User tidak ditemukan")
     * )
     */
    public function getUser()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }
            return response()->json($user);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Failed to fetch user profile'], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/users/byAuth",
     *     summary="Update data user yang sedang login",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email")
     *         )
     *     ),
     *     @OA\Response(response=200, description="User berhasil diupdate"),
     *     @OA\Response(response=500, description="Gagal update")
     * )
     */
    public function updateUser(Request $request)
    {
        try {
            $user = Auth::user();
            $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            ]);
            $user->update($request->only(['name', 'email']));
            return response()->json($user);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Failed to update user'], 500);
        }
    }
}