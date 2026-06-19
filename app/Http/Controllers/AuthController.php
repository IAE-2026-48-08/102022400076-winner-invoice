<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Services\RabbitMQPublisher;
use App\Services\SoapAuditService;
use App\Services\SsoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class AuthController extends Controller
{
    public function __construct(
        private readonly SsoService $ssoService,
        private readonly SoapAuditService $soapAuditService,
        private readonly RabbitMQPublisher $rabbitMQPublisher,
    ) {
    }

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        try {
            $authentication = $this->ssoService->authenticate(
                $credentials['email'],
                $credentials['password'],
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', $exception->getMessage());
        }

        $request->session()->regenerate();
        $request->session()->put([
            'sso.token' => $authentication['token'],
            'sso.user' => $authentication['user'],
        ]);

        $audit = $this->soapAuditService->auditLogin(
            $authentication['token'],
            $authentication['user']['email'],
        );

        $request->session()->put([
            'sso.audit_status' => $audit['status'],
            'sso.audit_receipt_number' => $audit['receipt_number'],
            'sso.audit_message' => $audit['message'],
        ]);

        if ($audit['success'] && $audit['receipt_number']) {
            $this->rabbitMQPublisher->publishEvent('winner.invoice.created', [
                'team_id' => config('services.sso.team_id'),
                'email' => $authentication['user']['email'],
                'activity' => 'Login Success',
                'audit_status' => $audit['status'],
                'audit_receipt_number' => $audit['receipt_number'],
            ]);
        }

        return redirect()
            ->route('dashboard')
            ->with('success', 'Login SSO berhasil.');
    }

    public function dashboard(Request $request): View
    {
        $token = (string) $request->session()->get('sso.token');

        return view('dashboard', [
            'ssoUser' => $request->session()->get('sso.user', []),
            'maskedToken' => $this->maskToken($token),
            'auditStatus' => $request->session()->get('sso.audit_status', 'Belum dikirim'),
            'receiptNumber' => $request->session()->get('sso.audit_receipt_number'),
            'auditMessage' => $request->session()->get('sso.audit_message'),
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('success', 'Anda telah logout.');
    }

    private function maskToken(string $token): string
    {
        if (strlen($token) <= 24) {
            return substr($token, 0, 8).'...';
        }

        return substr($token, 0, 16).'...'.substr($token, -8);
    }
}
