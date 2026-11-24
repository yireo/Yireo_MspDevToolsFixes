<?php declare(strict_types=1);

namespace Yireo\MspDevToolsFixes\Util;

class IpRangeMatcher
{
    /**
     * @param string $ip
     * @param string[] $ranges
     * @return bool
     */
    public function isMatched(string $ip, array $ranges): bool
    {
        $ipBinary = $this->toBinary($ip);
        if ($ipBinary === null) {
            return false;
        }

        foreach ($ranges as $range) {
            if ($this->isInRange($ipBinary, (string)$range)) {
                return true;
            }
        }

        return false;
    }

    private function isInRange(string $ipBinary, string $range): bool
    {
        $range = trim($range, " \f\n\r\t\v\x00");
        if ($range === '') {
            return false;
        }

        $parts = explode('/', $range, 2);
        $rangeBinary = $this->toBinary($parts[0]);
        if ($rangeBinary === null || strlen($rangeBinary) !== strlen($ipBinary)) {
            return false;
        }

        $maxPrefix = strlen($rangeBinary) * 8;
        $prefix = $maxPrefix;
        if (isset($parts[1])) {
            if (!preg_match('/^\d{1,3}$/', $parts[1])) {
                return false;
            }

            $prefix = (int)$parts[1];
        }

        if ($prefix < 1 || $prefix > $maxPrefix) {
            return false;
        }

        $fullBytes = intdiv($prefix, 8);
        if (substr($ipBinary, 0, $fullBytes) !== substr($rangeBinary, 0, $fullBytes)) {
            return false;
        }

        $remainingBits = $prefix % 8;
        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;

        return (ord($ipBinary[$fullBytes]) & $mask) === (ord($rangeBinary[$fullBytes]) & $mask);
    }

    private function toBinary(string $ip): ?string
    {
        $ip = trim($ip, " \f\n\r\t\v\x00");
        if (false === filter_var($ip, FILTER_VALIDATE_IP)) {
            return null;
        }

        $binary = inet_pton($ip);

        return $binary === false ? null : $binary;
    }
}
