<?php declare(strict_types=1);

namespace Yireo\MspDevToolsFixes\Test\Unit\Plugin;

use Laminas\Http\PhpEnvironment\Response as LaminasResponse;
use Magento\Framework\App\Response\Http as HttpResponse;
use MSP\DevTools\Model\CanInjectCode;
use MSP\DevTools\Model\ElementRegistry;
use MSP\DevTools\Model\EventRegistry;
use MSP\DevTools\Model\PageInfo;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Yireo\MspDevToolsFixes\Plugin\EnhancedResponsePlugin;
use Yireo\MspDevToolsFixes\Util\CspNonceGenerator;
use Yireo\MspDevToolsFixes\Util\PayloadRedactor;
use Yireo\MspDevToolsFixes\Util\ScriptRenderer;

class EnhancedResponsePluginTest extends TestCase
{
    private const PAGE = '<html><body><p>Hello</p></body></html>';

    protected function tearDown(): void
    {
        unset($GLOBALS['msp_devtools_profiler']);
    }

    public function testInjectsRedactedScriptBeforeBodyEnd(): void
    {
        $response = $this->createResponse(self::PAGE, 'text/html; charset=UTF-8');
        $pageInfo = ['general' => ['password' => 'secret123', 'q' => '</script>']];

        $this->createPlugin(true, $pageInfo)->beforeSendContent($response);

        $content = $response->getContent();
        $this->assertStringStartsWith('<html><body><p>Hello</p><script type="text/javascript" nonce="YWJj">', $content);
        $this->assertStringEndsWith('</script></body></html>', $content);
        $this->assertStringNotContainsString('secret123', $content);
        $this->assertStringContainsString('\u003C\/script\u003E', $content);
        $this->assertTrue($GLOBALS['msp_devtools_profiler']);
    }

    public function testInjectsWithoutContentTypeHeader(): void
    {
        $response = $this->createResponse(self::PAGE);
        $this->createPlugin(true, [])->beforeSendContent($response);

        $this->assertStringContainsString('window.mspDevTools', $response->getContent());
    }

    public function testSkipsWhenNotAllowed(): void
    {
        $response = $this->createResponse(self::PAGE, 'text/html');
        $this->createPlugin(false, [])->beforeSendContent($response);

        $this->assertSame(self::PAGE, $response->getContent());
    }

    public function testSkipsNonHtmlContentType(): void
    {
        $response = $this->createResponse(self::PAGE, 'application/xml');
        $this->createPlugin(true, [])->beforeSendContent($response);

        $this->assertSame(self::PAGE, $response->getContent());
    }

    public function testSkipsContentWithoutBodyEnd(): void
    {
        $response = $this->createResponse('%PDF-1.4 binary', 'text/html');
        $this->createPlugin(true, [])->beforeSendContent($response);

        $this->assertSame('%PDF-1.4 binary', $response->getContent());
    }

    public function testSkipsNonMagentoResponse(): void
    {
        $response = new LaminasResponse();
        $response->setContent(self::PAGE);
        $this->createPlugin(true, [])->beforeSendContent($response);

        $this->assertSame(self::PAGE, $response->getContent());
    }

    public function testKeepsContentWhenCollectingFails(): void
    {
        $response = $this->createResponse(self::PAGE, 'text/html');
        $pageInfo = $this->createMock(PageInfo::class);
        $pageInfo->method('getPageInfo')->willThrowException(new RuntimeException('Boom'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $this->createPlugin(true, $pageInfo, $logger)->beforeSendContent($response);

        $this->assertSame(self::PAGE, $response->getContent());
        $this->assertArrayNotHasKey('msp_devtools_profiler', $GLOBALS);
    }

    private function createResponse(string $content, ?string $contentType = null): HttpResponse
    {
        $response = $this->getMockBuilder(HttpResponse::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $response->setContent($content);
        if ($contentType !== null) {
            $response->getHeaders()->addHeaderLine('Content-Type', $contentType);
        }

        return $response;
    }

    private function createPlugin(
        bool $canInject,
        array|PageInfo $pageInfo,
        ?LoggerInterface $logger = null
    ): EnhancedResponsePlugin {
        $canInjectCode = $this->createMock(CanInjectCode::class);
        $canInjectCode->method('execute')->willReturn($canInject);

        if (is_array($pageInfo)) {
            $pageInfoData = $pageInfo;
            $pageInfo = $this->createMock(PageInfo::class);
            $pageInfo->method('getPageInfo')->willReturn($pageInfoData);
        }

        /** @var CspNonceGenerator&MockObject $nonceGenerator */
        $nonceGenerator = $this->createMock(CspNonceGenerator::class);
        $nonceGenerator->method('getNonce')->willReturn('YWJj');

        return new EnhancedResponsePlugin(
            $this->createMock(ElementRegistry::class),
            $this->createMock(EventRegistry::class),
            $pageInfo,
            $canInjectCode,
            $nonceGenerator,
            new PayloadRedactor(),
            new ScriptRenderer(),
            $logger ?? $this->createMock(LoggerInterface::class)
        );
    }
}
