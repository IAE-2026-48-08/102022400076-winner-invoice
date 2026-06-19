<?php

namespace App\Http\Controllers;

use OpenApi\Annotations as OA;
use App\Models\Winner;
use App\Models\Invoice;
use App\Models\AuctionItem;
use App\Models\User;
use App\Services\SoapAuditService;
use App\Services\RabbitMQPublisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Info(
 *     title="Winner Invoice API",
 *     version="1.0.0",
 *     description="API untuk layanan Winner & Invoice — IAE-T2 Compliant"
 * )
 *
 * @OA\Server(
 *     url="http://127.0.0.1:8000",
 *     description="Local Dev Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Masukkan token SSO JWT (format: Bearer <token>)"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="iaeKeyAuth",
 *     type="apiKey",
 *     in="header",
 *     name="X-IAE-KEY",
 *     description="Masukkan API Key IAE (header X-IAE-KEY)"
 * )
 */
class WinnerController extends Controller
{
    protected SoapAuditService $soapAuditService;
    protected RabbitMQPublisher $rabbitMQPublisher;

    public function __construct(SoapAuditService $soapAuditService, RabbitMQPublisher $rabbitMQPublisher)
    {
        $this->soapAuditService = $soapAuditService;
        $this->rabbitMQPublisher = $rabbitMQPublisher;
    }

    /**
     * GET /api/v1/winners
     * List all winners with related invoice, item, and user data.
     *
     * @OA\Get(
     *     path="/api/v1/winners",
     *     tags={"Winners"},
     *     summary="List semua pemenang",
     *     description="Mengembalikan daftar pemenang beserta data invoice, item, dan user.",
     *     @OA\Response(
     *         response=200,
     *         description="Berhasil",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="service_name", type="string", example="Winner-Service"),
     *                 @OA\Property(property="api_version", type="string", example="v1")
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $winners = Winner::with(['user', 'auctionItem', 'invoice'])->get();

        return response()->json($this->buildResponse('success', 'List of winners and invoices retrieved successfully', $winners), 200);
    }

    /**
     * GET /api/v1/winners/{id}
     * Get detail of a specific winner.
     *
     * @OA\Get(
     *     path="/api/v1/winners/{id}",
     *     tags={"Winners"},
     *     summary="Detail pemenang berdasarkan ID",
     *     @OA\Parameter(name="id", in="path", required=true, description="Winner ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Berhasil",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="service_name", type="string", example="Winner-Service"),
     *                 @OA\Property(property="api_version", type="string", example="v1")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Tidak ditemukan",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="errors", nullable=true, example=null)
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        $winner = Winner::with(['user', 'auctionItem', 'invoice'])->find($id);

        if (!$winner) {
            return response()->json($this->buildResponse('error', "Winner with ID {$id} not found", null, 404), 404);
        }

        return response()->json($this->buildResponse('success', 'Winner and invoice details retrieved successfully', $winner), 200);
    }

    /**
     * POST /api/v1/winners
     * Checkout a won auction item.
     *
     * @OA\Post(
     *     path="/api/v1/winners",
     *     tags={"Winners"},
     *     summary="Proses checkout pemenang (dilindungi JWT SSO)",
     *     security={{"bearerAuth":{}}, {"iaeKeyAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"auction_item_id","user_id","winning_bid"},
     *             @OA\Property(property="auction_item_id", type="integer", example=1),
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="winning_bid", type="number", format="float", example=5000000)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Checkout berhasil",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="service_name", type="string", example="Winner-Service"),
     *                 @OA\Property(property="api_version", type="string", example="v1")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized — token JWT atau X-IAE-KEY tidak valid",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="errors", nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validasi gagal",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Item sudah di-checkout",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="errors", nullable=true, example=null)
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'auction_item_id' => 'required|integer|exists:auction_items,id',
            'user_id' => 'required|integer|exists:users,id',
            'winning_bid' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($this->buildResponse('error', 'Validation error', ['errors' => $validator->errors()]), 422);
        }

        // Check if the item is already checked out (has a winner)
        $existingWinner = Winner::where('auction_item_id', $request->auction_item_id)->first();
        if ($existingWinner) {
            return response()->json($this->buildResponse('error', 'This auction item has already been checked out by a winner', null), 400);
        }

        $user = User::find($request->user_id);
        $item = AuctionItem::find($request->auction_item_id);

        try {
            $result = DB::transaction(function () use ($request, $user, $item) {
                // 1. Create the Winner record
                $winner = Winner::create([
                    'auction_item_id' => $request->auction_item_id,
                    'user_id' => $request->user_id,
                    'winning_bid' => $request->winning_bid,
                    'won_at' => now(),
                ]);

                // 2. Generate unique invoice number
                $invoiceNumber = 'INV/' . date('Ymd') . '/' . str_pad($winner->id, 4, '0', STR_PAD_LEFT);

                // 3. Trigger SOAP Audit (Critical Transaction validation)
                $receiptNumber = $this->soapAuditService->auditTransaction(
                    $winner->id,
                    $user->email,
                    $item->name,
                    (float) $request->winning_bid,
                    $request->bearerToken() ?? $request->session()->get('sso.token'),
                );

                // 4. Create the Invoice record
                $invoice = Invoice::create([
                    'winner_id' => $winner->id,
                    'invoice_number' => $invoiceNumber,
                    'amount' => $request->winning_bid,
                    'status' => 'pending', // Pending real payment, checkout creates the invoice
                    'receipt_number' => $receiptNumber,
                ]);

                // Update item status if necessary
                $item->update(['status' => 'completed']);

                return [
                    'winner' => $winner,
                    'invoice' => $invoice
                ];
            });

            $winner = $result['winner'];
            $invoice = $result['invoice'];

            // Reload relationships for response
            $winner->load(['user', 'auctionItem', 'invoice']);

            // 5. Broadcast asynchronously to RabbitMQ
            $this->rabbitMQPublisher->publishEvent('winner.invoice.created', [
                'winner_id' => $winner->id,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'item' => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'final_price' => $item->final_price,
                ],
                'invoice' => [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'amount' => $invoice->amount,
                    'receipt_number' => $invoice->receipt_number,
                    'status' => $invoice->status,
                ]
            ]);

            return response()->json($this->buildResponse('success', 'Checkout processed successfully', $winner), 201);

        } catch (\Exception $e) {
            Log::error("Checkout transaction failed", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json($this->buildResponse('error', 'Checkout failed due to system error: ' . $e->getMessage(), null), 500);
        }
    }

    private function buildResponse(string $status, string $message, mixed $data = null): array
    {
        if ($status === 'success') {
            return [
                'status' => 'success',
                'message' => $message,
                'data' => $data ?? [],
                'meta' => [
                    'service_name' => 'Winner-Service',
                    'api_version' => 'v1',
                ],
            ];
        }

        return [
            'status' => 'error',
            'message' => $message,
            'errors' => $data['errors'] ?? $data ?? null,
        ];
    }
}
