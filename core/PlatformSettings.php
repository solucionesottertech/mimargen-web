<?php
/**
 * MiMargen Platform Settings
 *
 * Loads platform-level configuration with sensible defaults.
 */
class PlatformSettings
{
    private static ?array $config = null;

    /**
     * Load platform settings from JSON or return defaults.
     *
     * @return array<string, mixed>
     */
    public static function load(): array
    {
        if (self::$config !== null) {
            return self::$config;
        }

        $path = DATA_DIR . '/settings.json';

        if (is_file($path)) {
            $json = file_get_contents($path);
            if ($json !== false) {
                $decoded = json_decode($json, true);
                if (is_array($decoded)) {
                    self::$config = array_merge(self::defaults(), $decoded);
                    return self::$config;
                }
            }
        }

        self::$config = self::defaults();
        return self::$config;
    }

    /**
     * Default configuration values.
     *
     * @return array<string, mixed>
     */
    private static function defaults(): array
    {
        return [
            'contact_email'      => 'hola@mimargen.cl',
            'contact_phone'      => '',
            'contact_whatsapp'   => '569XXXXXXXX',
            'contact_city'       => 'Chile',
            'hero_title'         => 'Conoce cuánto ganas realmente con cada producto',
            'hero_lead'          => 'Crea recetas con tus ingredientes, calcula el costo real de producción —incluyendo merma— y conoce tu margen de ganancia real. Todo en un solo lugar, sin hojas de cálculo que no te cierran.',
            'social_linkedin'    => 'https://linkedin.com/company/mimargen',
            'social_instagram'   => 'https://instagram.com/mimargen',
            'meta_title'         => 'MiMargen — Conoce el costo real de cada producto que fabricas',
            'meta_description'   => 'Calcula tu margen de ganancia de verdad. Costeo por receta, inventario, ventas y facturación electrónica — todo en uno. Diseñado para emprendedores y pequeños productores en Chile.',
            'og_image'           => '/og-image.png',
            'base_domain'        => 'mimargen.cl',
        ];
    }
}
