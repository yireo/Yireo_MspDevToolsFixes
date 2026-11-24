<?php declare(strict_types=1);

namespace Yireo\MspDevToolsFixes\Util;

use Magento\Csp\Helper\CspNonceProvider;
use Throwable;

class CspNonceGenerator
{
    public function __construct(
        private readonly CspNonceProvider $cspNonceProvider
    ) {
    }

    public function getNonce(): string
    {
        try {
            return $this->cspNonceProvider->generateNonce();
        } catch (Throwable $throwable) {
            return '';
        }
    }
}
