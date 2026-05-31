<?php
/**
 * Platform Settings
 *
 * Loads platform-level configuration from JSON storage with sensible defaults.
 * Matches OtterErp PlatformSettings contract.
 *
 * Signature: PlatformSettings::load(string $rootDataDir): array
 * Never throws — returns full defaults on corrupt/missing storage.
 */
class PlatformSettings
{
    /** Whitelist of allowed fields */
    public const FIELDS = [
        'contact_email',
        'contact_phone',
        'contact_whatsapp',
        'contact_city',
        'hero_title',
        'hero_lead',
        'social_linkedin',
        'social_instagram',
    ];

    private static ?array $cache = null;

    /**
     * Load platform settings from JSON or return defaults.
     *
     * @param string $rootDataDir Path to the data directory (e.g. dirname(__DIR__) . '/data')
     * @return array<string, mixed> Always returns all keys (defaults if missing)
     */
    public static function load(string $rootDataDir): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $path = $rootDataDir . '/_platform/settings.dat';

        if (is_file($path)) {
            $json = file_get_contents($path);
            if ($json !== false) {
                $decoded = json_decode($json, true);
                if (is_array($decoded)) {
                    // Merge only whitelisted fields over defaults
                    $filtered = [];
                    foreach (self::FIELDS as $field) {
                        $filtered[$field] = $decoded[$field] ?? self::defaults()[$field];
                    }
                    self::$cache = array_merge(self::defaults(), $filtered);
                    return self::$cache;
                }
            }
        }

        self::$cache = self::defaults();
        return self::$cache;
    }

    /**
     * Default configuration values.
     * Matches OtterErp defaults exactly.
     */
    private static function defaults(): array
    {
        return [
            'contact_email'      => null,
            'contact_phone'      => '+56 9 0000 0000',
            'contact_whatsapp'   => '56900000000',
            'contact_city'       => 'Santiago, Chile',
            'hero_title'         => 'El sistema que ordena tu empresa de punta a punta.',
            'hero_lead'          => 'Centraliza inventario, facturación electrónica SII, rendiciones, gastos y proveedores. Implementación en días y sin licencias por usuario.',
            'social_linkedin'    => null,
            'social_instagram'   => null,
        ];
    }

    /**
     * Get logo file info (mime type, size) from data directory.
     *
     * @param string $rootDataDir Path to data directory
     * @return array{mime?: string, size?: int}
     */
    public static function logoInfo(string $rootDataDir): array
    {
        $path = $rootDataDir . '/_platform/logo';
        if (!is_file($path)) {
            return [];
        }
        $mime = mime_content_type($path) ?: 'application/octet-stream';
        $size = filesize($path) ?: 0;
        return ['mime' => $mime, 'size' => $size];
    }

    /**
     * Read logo file and return as data URL for inline embedding.
     *
     * @param string $rootDataDir Path to data directory
     * @return string|null Data URL or null if no logo
     */
    public static function brandLogoDataUrl(string $rootDataDir): ?string
    {
        $path = $rootDataDir . '/_platform/logo';
        if (!is_file($path)) {
            return null;
        }
        $info = self::logoInfo($rootDataDir);
        $data = file_get_contents($path);
        if ($data === false) {
            return null;
        }
        return 'data:' . ($info['mime'] ?? 'image/png') . ';base64,' . base64_encode($data);
    }

    /**
     * Get the public URL path to serve the logo via HTTP.
     *
     * @param string $rootDataDir Path to data directory
     * @return string|null URL path or null
     */
    public static function brandLogoServeUrl(string $rootDataDir): ?string
    {
        $path = $rootDataDir . '/_platform/logo';
        if (!is_file($path)) {
            return null;
        }
        return '/api/logo';
    }
}
