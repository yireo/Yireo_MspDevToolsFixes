<?php declare(strict_types=1);

namespace Yireo\MspDevToolsFixes\Util;

use JsonException;
use MSP\DevTools\Model\Config;

class ScriptRenderer
{
    private const JSON_FLAGS = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR;

    /**
     * @param array $pageInfo
     * @param string $nonce
     * @return string
     * @throws JsonException
     */
    public function render(array $pageInfo, string $nonce): string
    {
        $nonceAttribute = '';
        if (preg_match('/^[A-Za-z0-9+\/=]+$/', $nonce)) {
            $nonceAttribute = ' nonce="' . $nonce . '"';
        }

        $html = '<script type="text/javascript"' . $nonceAttribute . '>';
        $html .= 'if (!window.mspDevTools) { window.mspDevTools = {}; }';
        foreach ($pageInfo as $key => $info) {
            $html .= 'window.mspDevTools[' . json_encode((string)$key, self::JSON_FLAGS) . '] = '
                . json_encode($info, self::JSON_FLAGS) . ';';
        }
        $html .= 'window.mspDevTools["_protocol"] = ' . (int)Config::PROTOCOL_VERSION . ';';
        $html .= '</script>';

        return $html;
    }
}
