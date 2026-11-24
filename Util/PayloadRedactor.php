<?php declare(strict_types=1);

namespace Yireo\MspDevToolsFixes\Util;

class PayloadRedactor
{
    public const MASK = '***';

    private const SENSITIVE_KEY_PATTERN =
        '/(pass|pwd|cc_|card|cvv|cvc|token|secret|api_key|apikey|private_key|form_key|authorization)/i';

    public function redact(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && preg_match(self::SENSITIVE_KEY_PATTERN, $key)) {
                $data[$key] = self::MASK;
                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->redact($value);
            }
        }

        return $data;
    }
}
