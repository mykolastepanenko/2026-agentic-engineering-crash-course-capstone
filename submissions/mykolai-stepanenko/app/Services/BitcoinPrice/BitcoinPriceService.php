<?php

namespace App\Services\BitcoinPrice;

use App\Exceptions\BitcoinPriceUnavailableException;
use App\Services\BitcoinPrice\Contracts\BitcoinPriceProvider;
use Psr\Log\LoggerInterface;
use Throwable;

class BitcoinPriceService
{
    /**
     * @param  list<BitcoinPriceProvider>  $providers
     */
    public function __construct(
        private readonly array $providers,
        private readonly LoggerInterface $logger,
    ) {}

    public function current(): BitcoinPrice
    {
        foreach ($this->providers as $provider) {
            try {
                return new BitcoinPrice($provider->fetchUahPrice(), $provider->name());
            } catch (Throwable $exception) {
                $this->logger->warning('Bitcoin price provider failed.', [
                    'provider' => $provider->name(),
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        throw new BitcoinPriceUnavailableException('Bitcoin price is temporarily unavailable.');
    }
}
