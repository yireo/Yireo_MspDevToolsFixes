<?php declare(strict_types=1);

namespace Yireo\MspDevToolsFixes\Plugin;
use Laminas\Http\PhpEnvironment\Response;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\Json\EncoderInterface;
use MSP\DevTools\Model\CanInjectCode;
use MSP\DevTools\Model\Config;
use MSP\DevTools\Model\ElementRegistry;
use MSP\DevTools\Model\EventRegistry;
use MSP\DevTools\Model\IsInjectableContentType;
use MSP\DevTools\Model\PageInfo;
use Yireo\MspDevToolsFixes\Util\CspNonceGenerator;

class EnhancedResponsePlugin
{
    /**
     * @var EncoderInterface
     */
    private $encoder;

    /**
     * @var ElementRegistry
     */
    private $elementRegistry;

    /**
     * @var EventRegistry
     */
    private $eventRegistry;

    /**
     * @var PageInfo
     */
    private $pageInfo;

    /**
     * @var IsInjectableContentType
     */
    private $isInjectableContentType;

    /**
     * @var CanInjectCode
     */
    private $canInjectCode;

    /**
     * @var CspNonceGenerator
     */
    private $cspNonceGenerator;

    public function __construct(
        EncoderInterface $encoder,
        ElementRegistry $elementRegistry,
        EventRegistry $eventRegistry,
        PageInfo $pageInfo,
        IsInjectableContentType $isInjectableContentType,
        CanInjectCode $canInjectCode,
        CspNonceGenerator $cspNonceGenerator
    ) {
        $this->encoder = $encoder;
        $this->elementRegistry = $elementRegistry;
        $this->eventRegistry = $eventRegistry;
        $this->pageInfo = $pageInfo;
        $this->isInjectableContentType = $isInjectableContentType;
        $this->canInjectCode = $canInjectCode;
        $this->cspNonceGenerator = $cspNonceGenerator;
    }

    public function beforeSendContent(
        Response $subject,
    ) {
        if (false === $this->canInjectCode->execute()) {
            return $subject;
        }

        if (false === $this->isInjectableContentType->execute($subject)) {
            return $subject;
        }

        if (false === $subject instanceof HttpResponse) {
            return $subject;
        }

        $this->elementRegistry->calcTimers();
        $this->eventRegistry->calcTimers();

        $scriptHtml = $this->getScriptHtml();
        $originalContent = $subject->getContent();
        $newContent = $originalContent.$scriptHtml;
        $subject->setContent($newContent);

        // We must use superglobals since profiler classes cannot access to object manager or DI system
        // See \MSP\DevTools\Profiler\Driver\Standard\Output\DevTools

        // @phpcs:ignore
        $GLOBALS['msp_devtools_profiler'] = true;

        return $subject;
    }

    private function getScriptHtml(): string
    {
        $pageInfo = $this->pageInfo->getPageInfo();
        $nonce = $this->cspNonceGenerator->getNonce();

        $html = '<script type="text/javascript" nonce="'.$nonce.'">';
        $html .= 'if (!window.mspDevTools) { window.mspDevTools = {}; }';
        foreach ($pageInfo as $key => $info) {
            $html .= 'window.mspDevTools["'.$key.'"] = '.$this->encoder->encode($info).';';
        }
        $html .= 'window.mspDevTools["_protocol"] = '.Config::PROTOCOL_VERSION.';';
        $html .= '</script>';

        return $html;
    }
}

