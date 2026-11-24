<?php declare(strict_types=1);

namespace Yireo\MspDevToolsFixes\Plugin;

use Magento\Framework\App\State;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use MSP\DevTools\Model\Config;
use Yireo\MspDevToolsFixes\Util\IpRangeMatcher;

class HardenIsActive
{
    public function __construct(
        private readonly State $appState,
        private readonly RemoteAddress $remoteAddress,
        private readonly IpRangeMatcher $ipRangeMatcher
    ) {
    }

    /**
     * @param Config $subject
     * @param mixed $result
     * @return bool
     */
    public function afterIsActive(Config $subject, mixed $result): bool
    {
        if ($result !== true) {
            return false;
        }

        if ($this->appState->getMode() !== State::MODE_DEVELOPER) {
            return false;
        }

        $ip = (string)$this->remoteAddress->getRemoteAddress();

        return $this->ipRangeMatcher->isMatched($ip, (array)$subject->getAllowedRanges());
    }
}
