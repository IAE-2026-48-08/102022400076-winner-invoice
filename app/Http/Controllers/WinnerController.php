<?php

namespace App\Http\Controllers;

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
     * View all winners with their invoices, items, and users.
     */
    public function index()
    {
        $winners = Winner::with(['user', 'auctionItem', 'invoice'])->get();

        return response()->json([
            'success' => true,
            'message' => 'List of winners and invoices retrieved successfully',
            'data' => $winners
        ], 200);
    }

    /**
     * GET /api/v1/winners/{id}
     * View specific winner details with invoice, item, and user.
     */
    public function show($id)
    {
        $winner = Winner::with(['user', 'auctionItem', 'invoice'])->find($id);

        if (!$winner) {
            return response()->json([
                'success' => false,
                'message' => "Winner with ID {$id} not found"
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Winner and invoice details retrieved successfully',
            'data' => $winner
        ], 200);
    }

    /**
     * POST /api/v1/winners
     * Checkout won auction item (creates Winner and Invoice, triggers SOAP Audit and RabbitMQ).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'auction_item_id' => 'required|integer|exists:auction_items,id',
            'user_id' => 'required|integer|exists:users,id',
            'winning_bid' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if the item is already checked out (has a winner)
        $existingWinner = Winner::where('auction_item_id', $request->auction_item_id)->first();
        if ($existingWinner) {
            return response()->json([
                'success' => false,
                'message' => 'This auction item has already been checked out by a winner'
            ], 400);
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
                    (float) $request->winning_bid
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
            $this->rabbitMQPublisher->publishEvent('winner.checkout', [
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

            return response()->json([
                'success' => true,
                'message' => 'Checkout processed successfully',
                'data' => $winner
            ], 201);

        } catch (\Exception $e) {
            Log::error("Checkout transaction failed", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Checkout failed due to system error: ' . $e->getMessage()
            ], 500);
        }
    }
}
