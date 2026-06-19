<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SsoAuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.sso.base_url' => 'https://iae-sso.virtualfri.id',
            'services.sso.api_key' => 'KEY-MHS-166',
            'services.sso.team_id' => 'TEAM-02',
            'services.soap.audit_url' => 'https://iae-sso.virtualfri.id/soap/v1/audit',
        ]);
    }

    public function test_dashboard_requires_sso_session(): void
    {
        $this->get('/')
            ->assertRedirect(route('login'));
    }

    public function test_web_checkout_uses_sso_session(): void
    {
        $this->withSession([
            'sso.token' => 'valid.sso.token',
            'sso.user' => ['email' => 'warga28@ktp.iae.id'],
        ])->postJson('/checkout', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Validation error');
    }

    public function test_successful_login_stores_session_and_soap_receipt(): void
    {
        Http::fake([
            'https://iae-sso.virtualfri.id/api/v1/auth/token' => Http::response([
                'data' => [
                    'access_token' => 'header.payload.signature',
                    'user' => [
                        'email' => 'warga28@ktp.iae.id',
                        'name' => 'Warga 28',
                    ],
                ],
            ]),
            'https://iae-sso.virtualfri.id/soap/v1/audit' => Http::response(
                '<?xml version="1.0"?><soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:iae="http://iae.central/audit"><soap:Body><iae:AuditResponse><iae:Status>SUCCESS</iae:Status><iae:ReceiptNumber>REC-LOGIN-166</iae:ReceiptNumber></iae:AuditResponse></soap:Body></soap:Envelope>',
                200,
                ['Content-Type' => 'text/xml']
            ),
            'https://iae-sso.virtualfri.id/api/v1/messages/publish' => Http::response([
                'status' => 'success',
                'exchange' => 'iae.central.exchange',
                'routing_key' => 'winner.invoice.created',
            ]),
        ]);

        $this->post('/login', [
            'email' => 'warga28@ktp.iae.id',
            'password' => 'secret-password',
        ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('sso.token', 'header.payload.signature')
            ->assertSessionHas('sso.user.email', 'warga28@ktp.iae.id')
            ->assertSessionHas('sso.audit_status', 'SUCCESS')
            ->assertSessionHas('sso.audit_receipt_number', 'REC-LOGIN-166');

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://iae-sso.virtualfri.id/api/v1/auth/token'
                && $request->hasHeader('X-API-Key', 'KEY-MHS-166')
                && data_get($request->data(), 'email') === 'warga28@ktp.iae.id';
        });

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://iae-sso.virtualfri.id/soap/v1/audit'
                && $request->hasHeader('Authorization', 'Bearer header.payload.signature')
                && str_contains($request->body(), '<iae:TeamID>TEAM-02</iae:TeamID>')
                && str_contains($request->body(), '<iae:ActivityName>UserLogin</iae:ActivityName>');
        });

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://iae-sso.virtualfri.id/api/v1/messages/publish'
                && $request['routing_key'] === 'winner.invoice.created'
                && $request['message']['data']['audit_receipt_number'] === 'REC-LOGIN-166';
        });

        $this->get('/')
            ->assertOk()
            ->assertSee('warga28@ktp.iae.id')
            ->assertSee('REC-LOGIN-166');
    }

    public function test_failed_sso_login_stays_on_login_page(): void
    {
        Http::fake([
            'https://iae-sso.virtualfri.id/api/v1/auth/token' => Http::response([
                'message' => 'Invalid credentials',
            ], 401),
        ]);

        $this->from('/login')
            ->post('/login', [
                'email' => 'warga28@ktp.iae.id',
                'password' => 'wrong-password',
            ])
            ->assertRedirect('/login')
            ->assertSessionHas('error', 'Invalid credentials')
            ->assertSessionMissing('sso.token');

        Http::assertSentCount(1);
    }

    public function test_soap_failure_does_not_cancel_successful_login(): void
    {
        Http::fake([
            'https://iae-sso.virtualfri.id/api/v1/auth/token' => Http::response([
                'access_token' => 'valid.jwt.token',
                'user' => ['email' => 'warga28@ktp.iae.id'],
            ]),
            'https://iae-sso.virtualfri.id/soap/v1/audit' => Http::response('Service unavailable', 503),
        ]);

        $this->post('/login', [
            'email' => 'warga28@ktp.iae.id',
            'password' => 'secret-password',
        ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('sso.token', 'valid.jwt.token')
            ->assertSessionHas('sso.audit_status', 'Gagal (HTTP 503)')
            ->assertSessionHas('sso.audit_message', 'Audit Log gagal dikirim.')
            ->assertSessionMissing('sso.audit_receipt_number');
    }
}
