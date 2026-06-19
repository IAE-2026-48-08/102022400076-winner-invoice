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
                'status' => 'error',
                'message' => 'Authorization token is required',
                'errors' => null
            ], 401);
        }

        $jwt = substr($authHeader, 7);
        $payload = $this->decodeAndVerifyJwt($jwt);

        if (!$payload) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired token',
                'errors' => null
            ], 401);
        }

        // Map JWT payload to local database
        $email = $payload['email'] ?? $payload['sub'] ?? null;
        $name = $payload['name'] ?? 'SSO User';
        $role = $payload['role'] ?? 'user';

        if (!$email) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token payload does not contain email or sub claim',
                'errors' => null
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

        // Decode Header
        $headerJson = $this->base64UrlDecode($headerB64);
        if (!$headerJson) {
            return null;
        }
        $header = json_decode($headerJson, true);
        if (!$header || !is_array($header)) {
            return null;
        }

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

        // 3. Verify Signature
        $alg = $header['alg'] ?? 'HS256';

        if ($alg === 'RS256') {
            $kid = $header['kid'] ?? null;
            if (!$kid) {
                Log::warning("SSO: JWT header does not contain kid claim");
                return null;
            }

            // Fetch keys from JWKS with cache
            $jwks = \Illuminate\Support\Facades\Cache::remember('iae_sso_jwks', 86400, function () {
                try {
                    $url = rtrim((string) config('services.sso.base_url'), '/') . '/api/v1/auth/jwks';
                    $response = \Illuminate\Support\Facades\Http::timeout(5)->get($url);
                    if ($response->successful()) {
                        return $response->json();
                    }
                } catch (\Throwable $e) {
                    Log::error('SSO: Failed to fetch JWKS from central server: ' . $e->getMessage());
                }
                return null;
            });

            if (!$jwks || !isset($jwks['keys']) || !is_array($jwks['keys'])) {
                Log::error('SSO: JWKS is unavailable or invalid');
                return null;
            }

            // Find matching kid
            $jwk = null;
            foreach ($jwks['keys'] as $key) {
                if (isset($key['kid']) && $key['kid'] === $kid) {
                    $jwk = $key;
                    break;
                }
            }

            if (!$jwk) {
                Log::warning("SSO: No matching key found in JWKS for kid: {$kid}");
                return null;
            }

            if (!isset($jwk['n']) || !isset($jwk['e'])) {
                Log::warning("SSO: JWK does not contain RSA parameters n and e");
                return null;
            }

            // Convert JWK to PEM public key
            try {
                $nBinary = $this->base64UrlDecode($jwk['n']);
                $eBinary = $this->base64UrlDecode($jwk['e']);

                if (!$nBinary || !$eBinary) {
                    Log::error('SSO: Failed to decode base64url modulus or exponent');
                    return null;
                }

                $publicKeyResource = openssl_pkey_new([
                    'rsa' => [
                        'n' => $nBinary,
                        'e' => $eBinary,
                    ]
                ]);

                if (!$publicKeyResource) {
                    Log::error('SSO: Failed to create public key resource from JWK');
                    return null;
                }

                $publicKeyDetails = openssl_pkey_get_details($publicKeyResource);
                if (!$publicKeyDetails || !isset($publicKeyDetails['key'])) {
                    Log::error('SSO: Failed to retrieve details from public key resource');
                    return null;
                }
                $publicKeyPem = $publicKeyDetails['key'];
            } catch (\Throwable $e) {
                Log::error('SSO: Error converting JWK to PEM: ' . $e->getMessage());
                return null;
            }

            // Verify signature using OpenSSL
            $signature = $this->base64UrlDecode($signatureB64);
            $dataToVerify = "$headerB64.$payloadB64";

            if (!$signature) {
                return null;
            }

            $verifyResult = openssl_verify($dataToVerify, $signature, $publicKeyPem, OPENSSL_ALGO_SHA256);
            if ($verifyResult !== 1) {
                if (env('SSO_BYPASS_SIGNATURE', false)) {
                    Log::warning("SSO: RS256 signature verification failed but BYPASS is enabled");
                    return $payload;
                }
                Log::warning("SSO: JWT RS256 signature verification failed");
                return null;
            }
        } elseif ($alg === 'HS256') {
            $key = env('SSO_JWT_KEY', 'dosen_secret_key');
            $expectedSignature = hash_hmac('sha256', "$headerB64.$payloadB64", $key, true);
            $expectedSignatureB64 = $this->base64UrlEncode($expectedSignature);

            if ($signatureB64 !== $expectedSignatureB64) {
                if (env('SSO_BYPASS_SIGNATURE', false)) {
                    Log::warning("SSO: HS256 signature mismatch but BYPASS is enabled");
                    return $payload;
                }
                Log::warning("SSO: JWT HS256 Signature verification failed");
                return null;
            }
        } else {
            Log::warning("SSO: Unsupported JWT algorithm: {$alg}");
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
