<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Role;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\Admin\UserResource;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

final class AuthController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /**
     * Login admin (email + password). Rate-limited 5/menit (throttle:login).
     * Mencatat upaya login ke cms_login_attempts + activity_logs.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $ip = $request->ip();

        $user = User::where('email', $request->email)->first();

        // catat upaya (sukses/gagal) untuk deteksi brute-force
        DB::table('cms_login_attempts')->insert([
            'email' => $request->email,
            'ip' => $ip,
            'success' => $user !== null && Hash::check($request->password, $user->password),
            'created_at' => now(),
        ]);

        if ($user === null || ! Hash::check($request->password, $user->password)) {
            $this->logger->log(
                userId: $user?->id,
                action: 'auth.login_failed',
                meta: ['email' => $request->email, 'ip' => $ip]
            );

            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi salah.',
            ]);
        }

        if (! $user->is_active) {
            $this->logger->log($user->id, 'auth.login_blocked_inactive');

            throw ValidationException::withMessages([
                'email' => 'Akun ini dinonaktifkan. Hubungi Super Admin.',
            ]);
        }

        // 2FA (TOTP) untuk Super Admin
        if ($user->isSuperAdmin() && filled($user->two_factor)) {
            $otp = $request->string('otp')->toString();
            if (! $this->verifyTotp($user, $otp)) {
                throw ValidationException::withMessages([
                    'otp' => 'Kode otentikasi dua faktor tidak valid.',
                ]);
            }
        }

        $user->update(['last_login_at' => now()]);

        // WAJIB: mulai sesi terautentikasi. Tanpa ini, endpoint admin kembali 401.
        Auth::login($user);
        $request->session()->regenerate();

        $this->logger->log($user->id, 'auth.login_success');

        return response()->json([
            'message' => 'Berhasil masuk.',
            'data' => new UserResource($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->logger->log($request->user()?->id, 'auth.logout');
        $request->session()->flush();

        return response()->json(['message' => 'Berhasil keluar.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($request->user()),
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate(['email' => 'required|email']);
        $status = Password::sendResetLink($validated);

        // selalu 200 untuk hindari enumeration email
        return response()->json([
            'message' => $status === Password::RESET_LINK_SENT
                ? 'Tautan reset kata sandi telah dikirim ke email kamu.'
                : 'Tautan reset kata sandi telah dikirim ke email kamu.',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => \Illuminate\Support\Str::random(60),
                ])->save();
                $this->logger->log($user->id, 'auth.password_reset');
            }
        );

        if ($status !== Password::RESET_THROTTLED && $status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'token' => 'Tautan reset tidak valid atau sudah kedaluwarsa.',
            ]);
        }

        return response()->json(['message' => 'Kata sandi berhasil diubah.']);
    }

    /**
     * Verifikasi TOTP (RFC 6238) 30 detik.
     */
    private function verifyTotp(User $user, ?string $otp): bool
    {
        if ($otp === null || $otp === '') {
            return false;
        }
        $secret = $user->two_factor['secret'] ?? null;
        if ($secret === null) {
            return false;
        }

        $window = 30;
        $code = $this->calculateTotp($secret, floor(time() / $window));

        return hash_equals((string) $code, (string) $otp);
    }

    private function calculateTotp(string $secret, float $counter): string
    {
        $binary = $this->base32Decode($secret);
        $pack = pack('N*', (int) $counter);
        $pack = str_pad($pack, 8, "\0", STR_PAD_LEFT);
        $hash = hash_hmac('sha1', $pack, $binary, true);
        $offset = ord($hash[19]) & 0x0F;
        $code = (
            ((ord($hash[$offset]) & 0x7F) << 24)
            | (ord($hash[$offset + 1]) << 16)
            | (ord($hash[$offset + 2]) << 8)
            | ord($hash[$offset + 3])
        ) % 1_000_000;

        return str_pad((string) $code, 6, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $b32): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $b32 = strtoupper(rtrim($b32, '='));
        $buffer = 0;
        $bits = 0;
        $output = '';

        foreach (str_split($b32) as $char) {
            $pos = strpos($alphabet, $char);
            if ($pos === false) {
                continue;
            }
            $buffer = ($buffer << 5) | $pos;
            $bits += 5;
            if ($bits >= 8) {
                $bits -= 8;
                $output .= chr(($buffer >> $bits) & 0xFF);
            }
        }

        return $output;
    }
}
