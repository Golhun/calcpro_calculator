<?php
declare(strict_types=1);

final class Fingerprint
{
    /**
     * Returns a stable fingerprint string, then hashed externally.
     * Keep the raw string private, never send raw identifiers to server if you want privacy.
     */
    public static function raw(): string
    {
        $parts = [];

        $parts[] = 'os:' . php_uname('s');
        $parts[] = 'osver:' . php_uname('r');
        $parts[] = 'machine:' . php_uname('m');

        // Hostname
        $hostname = gethostname() ?: '';
        $parts[] = 'host:' . $hostname;

        // Windows: use COMPUTERNAME as additional anchor
        $cn = getenv('COMPUTERNAME') ?: '';
        if ($cn) $parts[] = 'cn:' . $cn;

        // Linux: /etc/machine-id if present
        $mid = self::readFirstLine('/etc/machine-id');
        if ($mid) $parts[] = 'mid:' . $mid;

        // MAC address is often restricted, so do not rely on it by default.

        return implode('|', $parts);
    }

    public static function hash(): string
    {
        $raw = self::raw();
        $hex = hash('sha256', $raw);
        return 'sha256:' . $hex;
    }

    private static function readFirstLine(string $path): ?string
    {
        if (!is_file($path)) return null;
        $raw = @file_get_contents($path);
        if ($raw === false) return null;
        $raw = trim(strtok($raw, "\r\n"));
        return $raw !== '' ? $raw : null;
    }
}
