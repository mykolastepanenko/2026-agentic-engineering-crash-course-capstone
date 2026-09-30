<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BitcoinPriceUnavailableException;
use App\Http\Controllers\Controller;
use App\Services\BitcoinPrice\BitcoinPriceService;
use Illuminate\Http\JsonResponse;

class BitcoinPriceController extends Controller
{
    public function __invoke(BitcoinPriceService $bitcoinPriceService): JsonResponse
    {
        try {
            $price = $bitcoinPriceService->current();
        } catch (BitcoinPriceUnavailableException) {
            return response()->json(['message' => 'Bitcoin price is temporarily unavailable.'], 503);
        }

        return response()->json([
            'price' => $price->amount,
            'currency' => 'UAH',
            'provider' => $price->provider,
        ], options: JSON_PRESERVE_ZERO_FRACTION);
    }
}
