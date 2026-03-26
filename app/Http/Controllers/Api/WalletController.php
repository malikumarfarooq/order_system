<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\TopupWalletRequest;
use App\Http\Resources\WalletResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    use ApiResponse;

    public function show(Request $request): JsonResponse
    {
        return $this->success(
            new WalletResource($request->user()->wallet),
            'Wallet details'
        );
    }

    public function topup(TopupWalletRequest $request): JsonResponse
    {
        $wallet = $request->user()->wallet;
        $wallet->increment('balance', $request->amount);

        return $this->success(
            new WalletResource($wallet->fresh()),
            'Wallet topped up successfully'
        );
    }
}
