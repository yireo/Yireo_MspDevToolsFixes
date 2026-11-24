<?php declare(strict_types=1);

namespace Yireo\MspDevToolsFixes\Plugin;

use Laminas\Http\PhpEnvironment\Response;
use Magento\Framework\App\Response\Http as HttpResponse;
use MSP\DevTools\Model\CanInjectCode;
use MSP\DevTools\Model\ElementRegistry;
use MSP\DevTools\Model\EventRegistry;
use MSP\DevTools\Model\PageInfo;
use Psr\Log\LoggerInterface;
use Throwable;
use Yireo\MspDevToolsFixes\Util\CspNonceGenerator;
use Yireo\MspDevToolsFixes\Util\PayloadRedactor;
use Yireo\MspDevToolsFixes\Util\ScriptRenderer;

class EnhancedResponsePlugin
{
    public function __construct(
        private readonly ElementRegistry $elementRegistry,
        private readonly EventRegistry $eventRegistry,
        private readonly PageInfo $pageInfo,
        private readonly CanInjectCode $canInjectCode,
        private readonly CspNonceGenerator $cspNonceGenerator,
        private readonly PayloadRedactor $payloadRedactor,
        private readonly ScriptRenderer $scriptRenderer,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param Response $subject
     * @return void
     */
    public function beforeSendContent(Response $subject): void
    {
        if (false === $subject instanceof HttpResponse) {
            return;
        }

        if (false === $this->isHtmlResponse($subject)) {
            return;
        }

        $content = (string)$subject->getContent();
        $bodyEndPosition = strripos($content, '</body>');
        if ($bodyEndPosition === false) {
            return;
        }

        try {
            if (false === $this->canInjectCode->execute()) {
                return;
            }

            $this->elementRegistry->calcTimers();
            $this->eventRegistry->calcTimers();

            $pageInfo = $this->payloadRedactor->redact((array)$this->pageInfo->getPageInfo());
            $scriptHtml = $this->scriptRenderer->render($pageInfo, $this->cspNonceGenerator->getNonce());
        } catch (Throwable $throwable) {
            $this->logger->error('Yireo_MspDevToolsFixes: ' . $throwable->getMessage());
            return;
        }

        $subject->setContent(substr_replace($content, $scriptHtml, $bodyEndPosition, 0));

        // We must use superglobals since profiler classes cannot access to object manager or DI system
        // See \MSP\DevTools\Profiler\Driver\Standard\Output\DevTools
        // @phpcs:ignore
        $GLOBALS['msp_devtools_profiler'] = true;
    }

    private function isHtmlResponse(HttpResponse $response): bool
    {
        $contentType = $response->getHeader('Content-Type');
        if (false === $contentType) {
            return true;
        }

        $value = strtolower(trim((string)$contentType->getFieldValue(), " \f\n\r\t\v\x00"));

        return str_starts_with($value, 'text/html');
    }
}
