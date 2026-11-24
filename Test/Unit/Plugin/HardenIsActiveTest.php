<?php declare(strict_types=1);

namespace Yireo\MspDevToolsFixes\Test\Unit\Plugin;

use Magento\Framework\App\State;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use MSP\DevTools\Model\Config;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yireo\MspDevToolsFixes\Plugin\HardenIsActive;
use Yireo\MspDevToolsFixes\Util\IpRangeMatcher;

class HardenIsActiveTest extends TestCase
{
    /**
     * @dataProvider provideCases
     */
    #[DataProvider('provideCases')]
    public function testAfterIsActive(
        mixed $result,
        string $mode,
        string $ip,
        array $ranges,
        bool $expected
    ): void {
        $appState = $this->createMock(State::class);
        $appState->method('getMode')->willReturn($mode);

        $remoteAddress = $this->createMock(RemoteAddress::class);
        $remoteAddress->method('getRemoteAddress')->willReturn($ip);

        $config = $this->createMock(Config::class);
        $config->method('getAllowedRanges')->willReturn($ranges);

        $plugin = new HardenIsActive($appState, $remoteAddress, new IpRangeMatcher());
        $this->assertSame($expected, $plugin->afterIsActive($config, $result));
    }

    public static function provideCases(): array
    {
        $developer = State::MODE_DEVELOPER;

        return [
            'developer mode and matching IP' => [true, $developer, '127.0.0.1', ['127.0.0.1'], true],
            'upstream inactive' => [false, $developer, '127.0.0.1', ['127.0.0.1'], false],
            'upstream null' => [null, $developer, '127.0.0.1', ['127.0.0.1'], false],
            'production mode' => [true, State::MODE_PRODUCTION, '127.0.0.1', ['127.0.0.1'], false],
            'default mode' => [true, State::MODE_DEFAULT, '127.0.0.1', ['127.0.0.1'], false],
            'IP not matching' => [true, $developer, '8.8.8.8', ['127.0.0.1'], false],
            'upstream default range' => [true, $developer, '8.8.8.8', ['0.0.0.0/0'], false],
            'IPv6 client with empty range' => [true, $developer, '2001:db8::1', [''], false],
        ];
    }
}
