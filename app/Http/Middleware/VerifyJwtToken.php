<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyJwtToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json([
                'success' => false,
                'message' => 'Authorization token is required'
            ], 401);
        }

        $jwt = substr($authHeader, 7);
        $payload = $this->decodeAndVerifyJwt($jwt);

        if (!$payload) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token'
            ], 401);
        }

        // Map JWT payload to local database
        $email = $payload['email'] ?? $payload['sub'] ?? null;
        $name = $payload['name'] ?? 'SSO User';
        $role = $payload['role'] ?? 'user';

        if (!$email) {
            return response()->json([
                'success' => false,
                'message' => 'Token payload does not contain email or sub claim'
            ], 401);
        }

        // Find or create the user locally
        $user = User::where('email', $email)->first();

        if (!$user) {
            // Automatically register user from SSO if they do not exist
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(bin2hex(random_bytes(8))), // Dummy password
                'role' => $role,
            ]);
            Log::info("SSO: Registered new user locally: {$email} with role {$role}");
        } else {
            // Update role if changed
            if ($user->role !== $role) {
                $user->update(['role' => $role]);
                Log::info("SSO: Updated role for user: {$email} to {$role}");
            }
        }

        // Attach authenticated user to the request
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }

    /**
     * Decode and verify JWT.
     */
    protected function decodeAndVerifyJwt(string $jwt): ?array
    {
        $parts = explode('.', $jwt);

        if (count($parts) !== 3) {
            return null;
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        // 1. Decode Payload
        $payloadJson = $this->base64UrlDecode($payloadB64);
        if (!$payloadJson) {
            return null;
        }

        $payload = json_decode($payloadJson, true);
        if (!$payload) {
            return null;
        }

        // 2. Check Expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            Log::warning("SSO: JWT has expired", ['exp' => $payload['exp'], 'now' => time()]);
            return null;
        }

        // 3. Verify Signature (HMAC SHA256 using key from env)
        $key = env('SSO_JWT_KEY', 'dosen_secret_key');
        $expectedSignature = hash_hmac('sha256', "$headerB64.$payloadB64", $key, true);
        $expectedSignatureB64 = $this->base64UrlEncode($expectedSignature);

        // For flexibility in development/testing, if key is set to a specific dev bypass or if signature matches
        if ($signatureB64 !== $expectedSignatureB64) {
            // For testing/mocking purposes, we can log a warning but still accept the signature if it's set to ignore/stub
            if (env('SSO_BYPASS_SIGNATURE', false)) {
                Log::warning("SSO: Signature mismatch but BYPASS is enabled");
                return $payload;
            }
            Log::warning("SSO: JWT Signature verification failed");
            return null;
        }

        return $payload;
    }

    protected function base64UrlDecode(string $input): ?string
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        $decoded = base64_decode(strtr($input, '-_', '+/'));
        return $decoded === false ? null : $decoded;
    }

    protected function base64UrlEncode(string $input): string
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }
}
