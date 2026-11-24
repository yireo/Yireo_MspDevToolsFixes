<?php declare(strict_types=1);

namespace Yireo\MspDevToolsFixes\Test\Unit\Util;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yireo\MspDevToolsFixes\Util\IpRangeMatcher;

class IpRangeMatcherTest extends TestCase
{
    /**
     * @dataProvider provideMatches
     */
    #[DataProvider('provideMatches')]
    public function testIsMatched(string $ip, array $ranges, bool $expected): void
    {
        $this->assertSame($expected, (new IpRangeMatcher())->isMatched($ip, $ranges));
    }

    public static function provideMatches(): array
    {
        return [
            'exact IPv4' => ['127.0.0.1', ['127.0.0.1'], true],
            'IPv4 /32' => ['127.0.0.1', ['127.0.0.1/32'], true],
            'IPv4 /8' => ['10.1.2.3', ['10.0.0.0/8'], true],
            'IPv4 /12 upper bound' => ['172.31.255.254', ['172.16.0.0/12'], true],
            'IPv4 /12 outside' => ['172.32.0.1', ['172.16.0.0/12'], false],
            'IPv4 different host' => ['127.0.0.2', ['127.0.0.1'], false],
            'second range matches' => ['192.168.1.5', ['10.0.0.0/8', ' 192.168.1.0/24 '], true],
            'IPv4 match-all rejected' => ['8.8.8.8', ['0.0.0.0/0'], false],
            'IPv6 match-all rejected' => ['2001:db8::1', ['::/0'], false],
            'IPv6 exact' => ['::1', ['::1'], true],
            'IPv6 /32' => ['2001:db8:ffff::1', ['2001:db8::/32'], true],
            'IPv6 /32 outside' => ['2001:db9::1', ['2001:db8::/32'], false],
            'IPv6 client against IPv4 ranges' => ['2001:db8::1', ['127.0.0.1', '10.0.0.0/8'], false],
            'IPv4 client against IPv6 range' => ['10.0.0.1', ['::/1'], false],
            'empty range' => ['2001:db8::1', [''], false],
            'trailing comma entry' => ['2001:db8::1', ['127.0.0.1', ''], false],
            'hostname entry' => ['2001:db8::1', ['localhost'], false],
            'non-numeric netmask' => ['10.0.0.1', ['10.0.0.0/abc'], false],
            'netmask too large' => ['10.0.0.1', ['10.0.0.0/33'], false],
            'empty netmask' => ['10.0.0.1', ['10.0.0.0/'], false],
            'invalid client IP' => ['not-an-ip', ['10.0.0.0/8'], false],
            'empty client IP' => ['', ['127.0.0.1'], false],
            'no ranges' => ['127.0.0.1', [], false],
        ];
    }
}
