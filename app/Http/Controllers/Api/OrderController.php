<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\IdempotencyKey;
use App\Models\Order;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    use ApiResponse;

    // List all orders for authenticated user
    public function index(Request $request): JsonResponse
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return $this->success(
            OrderResource::collection($orders),
            'Orders retrieved successfully'
        );
    }

    // Show single order
    public function show(Request $request, int $id): JsonResponse
    {
        $order = Order::where('user_id', $request->user()->id)
            ->findOrFail($id);

        return $this->success(
            new OrderResource($order),
            'Order retrieved successfully'
        );
    }

    // Create new order
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $user = $request->user();
        $idempotencyKey = $request->idempotency_key;

        // Step 1 — Check idempotency key (duplicate request?)
        $existingKey = IdempotencyKey::where('user_id', $user->id)
            ->where('key', $idempotencyKey)
            ->first();

        if ($existingKey) {
            // Return saved response — do not create duplicate order
            return response()->json([
                'status'  => true,
                'message' => 'Duplicate request detected - returning original response',
                'data'    => $existingKey->response,
            ], 200);
        }

        // Step 2 — Check wallet balance
        $wallet = $user->wallet;

        if (!$wallet->hasSufficientBalance($request->amount)) {
            return $this->error(
                'Insufficient wallet balance',
                422
            );
        }

        // Step 3 — Database Transaction
        // If anything fails, everything rolls back automatically
        try {
            $order = DB::transaction(function () use ($request, $user, $wallet, $idempotencyKey) {

                // Deduct wallet balance
                $wallet->deduct($request->amount);

                // Create order
                $order = Order::create([
                    'user_id'     => $user->id,
                    'title'       => $request->title,
                    'description' => $request->description,
                    'amount'      => $request->amount,
                    'status'      => Order::STATUS_PENDING,
                ]);

                // Save idempotency key with response
                IdempotencyKey::create([
                    'user_id'    => $user->id,
                    'key'        => $idempotencyKey,
                    'response'   => new OrderResource($order),
                    'expires_at' => now()->addDays(1),
                ]);

                return $order;
            });

            return $this->success(
                new OrderResource($order),
                'Order created successfully',
                201
            );
        } catch (\Exception $e) {
            // Log the error for monitoring
            Log::error('Order creation failed', [
                'user_id' => $user->id,
                'amount'  => $request->amount,
                'error'   => $e->getMessage(),
            ]);

            return $this->error(
                'Order creation failed. Please try again.',
                500
            );
        }
    }

    // Update order status
    public function updateStatus(
        UpdateOrderStatusRequest $request,
        int $id
    ): JsonResponse {
        $order = Order::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $newStatus = $request->status;

        // Check if transition is valid
        if (!$order->canTransitionTo($newStatus)) {
            return $this->error(
                "Cannot transition order from '{$order->status}' to '{$newStatus}'",
                422
            );
        }

        $order->update(['status' => $newStatus]);

        return $this->success(
            new OrderResource($order->fresh()),
            'Order status updated successfully'
        );
    }
}
