<?php declare(strict_types=1);

namespace Yireo\MspDevToolsFixes\Plugin;

use Magento\Framework\App\Response\Http as HttpResponse;
use MSP\DevTools\Model\CanInjectCode;

class NoCacheWhenInjecting
{
    public function __construct(
        private readonly CanInjectCode $canInjectCode
    ) {
    }

    /**
     * @param HttpResponse $subject
     * @return void
     */
    public function beforeSendResponse(HttpResponse $subject): void
    {
        if ($this->canInjectCode->execute()) {
            $subject->setNoCacheHeaders();
        }
    }
}
