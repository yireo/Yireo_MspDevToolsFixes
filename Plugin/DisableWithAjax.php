<?php declare(strict_types=1);

namespace Yireo\MspDevToolsFixes\Plugin;

use Magento\Framework\App\Request\Http;
use MSP\DevTools\Model\Config;

class DisableWithAjax
{
    /**
     * @var Http
     */
    private $http;

    /**
     * DisableWithAjax constructor.
     * @param Http $http
     */
    public function __construct(Http $http)
    {
        $this->http = $http;
    }

    /**
     * @param Config $config
     * @param bool $return
     * @return bool
     */
    public function afterCanInjectCode(Config $config, bool $return)
    {
        if ($return === true && $this->isAjax()) {
            return false;
        }

        return $return;
    }

    /**
     * @return bool
     */
    private function isAjax(): bool
    {
        $contentType = $this->http->getHeader('Content-Type');
        if ($contentType === 'application/json') {
            return true;
        }

        return false;
    }
}
