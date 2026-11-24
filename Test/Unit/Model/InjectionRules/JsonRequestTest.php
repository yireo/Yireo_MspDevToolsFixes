<?php declare(strict_types=1);

namespace Yireo\MspDevToolsFixes\Test\Unit\Model\InjectionRules;

use Magento\Framework\App\Request\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yireo\MspDevToolsFixes\Model\InjectionRules\JsonRequest;

class JsonRequestTest extends TestCase
{
    /**
     * @dataProvider provideHeaders
     */
    #[DataProvider('provideHeaders')]
    public function testExecute(mixed $contentType, mixed $accept, bool $expected): void
    {
        $http = $this->createMock(Http::class);
        $http->method('getHeader')->willReturnMap([
            ['Content-Type', false, $contentType],
            ['Accept', false, $accept],
        ]);

        $this->assertSame($expected, (new JsonRequest($http))->execute());
    }

    public static function provideHeaders(): array
    {
        return [
            'browser page request' => [false, 'text/html,application/xhtml+xml,*/*;q=0.8', true],
            'no headers' => [false, false, true],
            'form post' => ['application/x-www-form-urlencoded', 'text/html', true],
            'JSON content type' => ['application/json', false, false],
            'JSON content type with charset' => ['application/json; charset=utf-8', false, false],
            'JSON content type uppercase' => ['Application/JSON', false, false],
            'JSON accept' => [false, 'application/json, text/javascript, */*; q=0.01', false],
        ];
    }
}
