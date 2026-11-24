<?php declare(strict_types=1);

namespace Yireo\MspDevToolsFixes\Plugin;

use Magento\Csp\Api\CspRendererInterface;
use Magento\Framework\App\Response\Http as HttpResponse;
use MSP\DevTools\Model\CanInjectCode;
use Yireo\MspDevToolsFixes\Util\CspNonceGenerator;

class PregenerateCspNonce
{
    public function __construct(
        private readonly CanInjectCode $canInjectCode,
        private readonly CspNonceGenerator $cspNonceGenerator
    ) {
    }

    /**
     * @param CspRendererInterface $subject
     * @param HttpResponse $response
     * @return void
     */
    public function beforeRender(CspRendererInterface $subject, HttpResponse $response): void
    {
        if ($this->canInjectCode->execute()) {
            $this->cspNonceGenerator->getNonce();
        }
    }
}
