<?php declare(strict_types=1);

namespace Yireo\MspDevToolsFixes\Model\InjectionRules;

use Magento\Framework\App\Request\Http;
use MSP\DevTools\Api\RuleInterface;

class JsonRequest implements RuleInterface
{
    public function __construct(
        private readonly Http $http
    ) {
    }

    public function execute(): bool
    {
        foreach (['Content-Type', 'Accept'] as $headerName) {
            if ($this->isJson($this->http->getHeader($headerName))) {
                return false;
            }
        }

        return true;
    }

    private function isJson(mixed $headerValue): bool
    {
        if (!is_string($headerValue)) {
            return false;
        }

        return stripos(ltrim($headerValue, " \f\n\r\t\v\x00"), 'application/json') === 0;
    }
}
