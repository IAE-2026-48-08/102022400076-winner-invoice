<?php

namespace Tests\Feature;

use App\Models\AuctionItem;
use App\Models\User;
use App\Models\Winner;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WinnerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper to generate a valid test JWT.
     */
    protected function generateTestJwt(string $email, string $name, string $role = 'user'): string
    {
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload = json_encode([
            'sub' => $email,
            'email' => $email,
            'name' => $name,
            'role' => $role,
            'exp' => time() + 3600
        ]);

        $base64UrlHeader = str_replace('=', '', strtr(base64_encode($header), '+/', '-_'));
        $base64UrlPayload = str_replace('=', '', strtr(base64_encode($payload), '+/', '-_'));

        $signature = hash_hmac('sha256', "$base64UrlHeader.$base64UrlPayload", 'dosen_secret_key', true);
        $base64UrlSignature = str_replace('=', '', strtr(base64_encode($signature), '+/', '-_'));

        return "$base64UrlHeader.$base64UrlPayload.$base64UrlSignature";
    }

    /**
     * Test GET /api/v1/winners returns all winners.
     */
    public function test_get_all_winners(): void
    {
        // Setup data
        $user = User::factory()->create(['email' => 'buyer@example.com']);
        $item = AuctionItem::create([
            'name' => 'Test Item',
            'final_price' => 100000.00,
            'status' => 'completed'
        ]);
        $winner = Winner::create([
            'auction_item_id' => $item->id,
            'user_id' => $user->id,
            'winning_bid' => 100000.00
        ]);
        Invoice::create([
            'winner_id' => $winner->id,
            'invoice_number' => 'INV-001',
            'amount' => 100000.00,
            'status' => 'pending'
        ]);

        $response = $this->getJson('/api/v1/winners');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'auction_item_id',
                        'user_id',
                        'winning_bid',
                        'user',
                        'auction_item',
                        'invoice'
                    ]
                ]
            ]);
    }

    /**
     * Test GET /api/v1/winners/{id} returns specific winner.
     */
    public function test_get_winner_by_id(): void
    {
        // Setup data
        $user = User::factory()->create(['email' => 'buyer@example.com']);
        $item = AuctionItem::create([
            'name' => 'Test Item',
            'final_price' => 100000.00,
            'status' => 'completed'
        ]);
        $winner = Winner::create([
            'auction_item_id' => $item->id,
            'user_id' => $user->id,
            'winning_bid' => 100000.00
        ]);
        Invoice::create([
            'winner_id' => $winner->id,
            'invoice_number' => 'INV-001',
            'amount' => 100000.00,
            'status' => 'pending'
        ]);

        $response = $this->getJson("/api/v1/winners/{$winner->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $winner->id)
            ->assertJsonPath('data.invoice.invoice_number', 'INV-001');
    }

    /**
     * Test GET /api/v1/winners/{id} returns 404 for invalid ID.
     */
    public function test_get_winner_not_found(): void
    {
        $response = $this->getJson('/api/v1/winners/999');
        $response->assertStatus(404)
            ->assertJsonPath('status', 'error');
    }

    /**
     * Test POST /api/v1/winners requires Authorization token.
     */
    public function test_checkout_requires_jwt_token(): void
    {
        $response = $this->postJson('/api/v1/winners', [
            'auction_item_id' => 1,
            'user_id' => 1,
            'winning_bid' => 50000
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Authorization token is required');
    }

    /**
     * Test POST /api/v1/winners processes successfully with valid JWT token.
     */
    public function test_checkout_success_with_jwt(): void
    {
        // Mock SOAP API call to avoid external dependency and make test fast
        Http::fake([
            'https://iae-sso.virtualfri.id/soap/v1/audit' => Http::response(
                '<soapenv:Envelope><soapenv:Body><AuditResponse><ReceiptNumber>REC-TEST-SOAP-12345</ReceiptNumber></AuditResponse></soapenv:Body></soapenv:Envelope>',
                200
            ),
            'https://iae-sso.virtualfri.id/api/v1/auth/token' => Http::response([
                'status' => 'success',
                'token_type' => 'm2m',
                'token' => 'machine.jwt.token',
            ]),
            'https://iae-sso.virtualfri.id/api/v1/messages/publish' => Http::response([
                'status' => 'success',
                'exchange' => 'iae.central.exchange',
                'routing_key' => 'winner.invoice.created',
            ]),
        ]);

        // Setup data
        $user = User::factory()->create([
            'email' => 'buyer@example.com',
            'name' => 'Buyer Test',
            'role' => 'user'
        ]);
        $item = AuctionItem::create([
            'name' => 'Exclusive Ring',
            'final_price' => 5000000.00,
            'status' => 'completed'
        ]);

        $jwt = $this->generateTestJwt('buyer@example.com', 'Buyer Test', 'user');

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$jwt}"
        ])->postJson('/api/v1/winners', [
            'auction_item_id' => $item->id,
            'user_id' => $user->id,
            'winning_bid' => 5000000.00
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.invoice.amount', 5000000)
            ->assertJsonPath('data.invoice.receipt_number', 'REC-TEST-SOAP-12345');

        // Confirm database states
        $this->assertDatabaseHas('winners', [
            'auction_item_id' => $item->id,
            'user_id' => $user->id,
            'winning_bid' => 5000000.00
        ]);

        $this->assertDatabaseHas('invoices', [
            'amount' => 5000000.00,
            'receipt_number' => 'REC-TEST-SOAP-12345'
        ]);

        Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
            return $request->url() === 'https://iae-sso.virtualfri.id/api/v1/messages/publish'
                && $request->hasHeader('Authorization', 'Bearer machine.jwt.token')
                && $request['routing_key'] === 'winner.invoice.created'
                && $request['message']['event_name'] === 'WinnerInvoiceCreated';
        });

        Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
            return $request->url() === 'https://iae-sso.virtualfri.id/soap/v1/audit'
                && $request->hasHeader('Authorization', 'Bearer machine.jwt.token')
                && str_contains($request->body(), '<iae:ActivityName>WinnerCheckout</iae:ActivityName>');
        });
    }
}
