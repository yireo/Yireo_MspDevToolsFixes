<?php declare(strict_types=1);

namespace Yireo\MspDevToolsFixes\Test\Unit\Util;

use PHPUnit\Framework\TestCase;
use Yireo\MspDevToolsFixes\Util\PayloadRedactor;

class PayloadRedactorTest extends TestCase
{
    public function testRedactsSensitiveKeysRecursively(): void
    {
        $data = [
            'general' => [
                [
                    'id' => 'request',
                    'value' => [
                        'login' => ['username' => 'jane@example.com', 'Password' => 'secret123'],
                        'payment' => ['cc_number' => '4111111111111111', 'CVV' => '123'],
                        'form_key' => 'abc',
                        'access_token' => ['nested' => 'value'],
                        'q' => 'shoes',
                    ],
                ],
            ],
            'version' => 2,
        ];

        $result = (new PayloadRedactor())->redact($data);
        $request = $result['general'][0]['value'];

        $this->assertSame('jane@example.com', $request['login']['username']);
        $this->assertSame(PayloadRedactor::MASK, $request['login']['Password']);
        $this->assertSame(PayloadRedactor::MASK, $request['payment']['cc_number']);
        $this->assertSame(PayloadRedactor::MASK, $request['payment']['CVV']);
        $this->assertSame(PayloadRedactor::MASK, $request['form_key']);
        $this->assertSame(PayloadRedactor::MASK, $request['access_token']);
        $this->assertSame('shoes', $request['q']);
        $this->assertSame('request', $result['general'][0]['id']);
        $this->assertSame(2, $result['version']);
    }

    public function testKeepsNumericKeysAndNonStringValues(): void
    {
        $data = [0 => 'password', 1 => null, 'count' => 5, 'enabled' => true];

        $this->assertSame($data, (new PayloadRedactor())->redact($data));
    }
}
