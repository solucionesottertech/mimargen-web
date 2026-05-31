<?php
/**
 * MiMargen Landing Page
 *
 * Entry point for the apex domain (mimargen.cl).
 * Bootstraps the PHP backend, injects PlatformSettings variables,
 * generates canonical/OG meta tags, and handles the honeypot lead-capture form.
 */
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/PlatformSettings.php';
require_once __DIR__ . '/core/JsonStorage.php';

$settings = PlatformSettings::load();

$contactEmail    = $settings['contact_email']    ?? 'hola@mimargen.cl';
$contactPhone    = $settings['contact_phone']    ?? '';
$contactWhatsApp = $settings['contact_whatsapp'] ?? '569XXXXXXXX';
$contactCity     = $settings['contact_city']     ?? 'Chile';
$heroTitle       = $settings['hero_title']       ?? 'Conoce cuánto ganas realmente con cada producto';
$heroLead        = $settings['hero_lead']        ?? '';
$socialLinkedin  = $settings['social_linkedin']  ?? 'https://linkedin.com/company/mimargen';
$socialInstagram = $settings['social_instagram'] ?? 'https://instagram.com/mimargen';
$metaTitle       = $settings['meta_title']       ?? 'MiMargen — Conoce el costo real de cada producto que fabricas';
$metaDescription = $settings['meta_description'] ?? 'Calcula tu margen de ganancia de verdad. Costeo por receta, inventario, ventas y facturación electrónica — todo en uno.';
$ogImage         = $settings['og_image']         ?? '/og-image.png';

// Honeypot + lead form handling
$formError   = '';
$formSuccess = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'lead') {
    if (!empty($_POST['website'] ?? '')) {
        $formSuccess = true; // honeypot — silent success
    } else {
        $email = trim((string)($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $formError = 'Necesitamos un correo válido.';
        } else {
            try {
                JsonStorage::save('leads', [
                    'email'      => $email,
                    'nombre'     => trim((string)($_POST['nombre'] ?? '')),
                    'empresa'    => trim((string)($_POST['empresa'] ?? '')),
                    'telefono'   => trim((string)($_POST['telefono'] ?? '')),
                    'mensaje'    => trim((string)($_POST['mensaje'] ?? '')),
                    'ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
                    'user_agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                    'created_at' => date('c'),
                ]);
                $formSuccess = true;
            } catch (Throwable $e) {
                $formError = 'No pudimos guardar tu solicitud. Escríbenos a ' . htmlspecialchars($contactEmail) . '.';
            }
        }
    }
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$canonical = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'mimargen.cl') . '/';
$year = date('Y');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($metaTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($canonical) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($metaTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'mimargen.cl') . $ogImage) ?>">

    <!-- JSON-LD SoftwareApplication schema -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "SoftwareApplication",
      "name": "MiMargen",
      "description": "Software de gestión para pequeños productores con costeo por receta, inventario, ventas y facturación electrónica.",
      "url": "https://mimargen.cl",
      "applicationCategory": "BusinessApplication",
      "operatingSystem": "Web",
      "offers": {
        "@type": "Offer",
        "price": "29990",
        "priceCurrency": "CLP",
        "description": "Desde $29.990 CLP/mes"
      },
      "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "4.8",
        "ratingCount": "127"
      }
    }
    </script>

    <link rel="stylesheet" href="/assets/landing.css">
    <link rel="preload" href="/fonts/inter-var.woff2" as="font" type="font/woff2" crossorigin>
</head>
<body class="bg-cream-50 text-slate-900 antialiased">

<!-- ==================== HEADER ==================== -->
<header class="fixed top-0 left-0 right-0 z-50 bg-cream-50/80 backdrop-blur-md border-b border-slate-200/60">
    <nav class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <!-- Logo -->
        <a href="#" class="flex items-center gap-2 group">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 flex items-center justify-center shadow-md shadow-brand-500/20 group-hover:shadow-brand-500/40 transition-shadow">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
            </div>
            <span class="text-lg font-bold text-slate-900">Mi<span class="text-brand-600">Margen</span></span>
        </a>

        <!-- Desktop Nav -->
        <div class="hidden md:flex items-center gap-6">
            <a href="#producto" class="text-sm font-medium text-slate-600 hover:text-brand-600 transition-colors">Producto</a>
            <a href="#precios" class="text-sm font-medium text-slate-600 hover:text-brand-600 transition-colors">Precios</a>
            <a href="#calculadora" class="text-sm font-medium text-slate-600 hover:text-brand-600 transition-colors">Calculadora</a>
            <a href="#faq" class="text-sm font-medium text-slate-600 hover:text-brand-600 transition-colors">FAQ</a>
            <a href="#precios" class="ml-2 inline-flex items-center px-4 py-2 rounded-lg bg-brand-600 text-white text-sm font-semibold hover:bg-brand-700 transition-colors shadow-md shadow-brand-600/20 hover:shadow-brand-600/30">Pruébalo gratis</a>
        </div>

        <!-- Mobile Menu Button -->
        <button id="mobile-menu-btn" class="md:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100 transition-colors" aria-label="Abrir menú">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </nav>

    <!-- Mobile Menu -->
    <div id="mobile-menu" class="hidden md:hidden bg-cream-50 border-t border-slate-200/60">
        <div class="px-4 py-4 space-y-3">
            <a href="#producto" class="block text-sm font-medium text-slate-600 hover:text-brand-600 transition-colors py-2">Producto</a>
            <a href="#precios" class="block text-sm font-medium text-slate-600 hover:text-brand-600 transition-colors py-2">Precios</a>
            <a href="#calculadora" class="block text-sm font-medium text-slate-600 hover:text-brand-600 transition-colors py-2">Calculadora</a>
            <a href="#faq" class="block text-sm font-medium text-slate-600 hover:text-brand-600 transition-colors py-2">FAQ</a>
            <a href="#precios" class="block text-center px-4 py-2.5 rounded-lg bg-brand-600 text-white text-sm font-semibold hover:bg-brand-700 transition-colors">Pruébalo gratis</a>
        </div>
    </div>
</header>

<main>
<!-- ==================== HERO ==================== -->
<section class="relative pt-32 pb-20 sm:pt-40 sm:pb-28 overflow-hidden">
    <!-- Background decoration -->
    <div class="absolute inset-0 -z-10">
        <div class="absolute top-0 right-0 w-96 h-96 bg-brand-200/30 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>
        <div class="absolute bottom-0 left-0 w-80 h-80 bg-accent-200/20 rounded-full blur-3xl translate-y-1/2 -translate-x-1/2"></div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            <!-- Text + Lead Form -->
            <div class="text-center lg:text-left">
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 leading-tight tracking-tight">
                    <?= htmlspecialchars($heroTitle) ?>
                </h1>
                <p class="mt-6 text-lg sm:text-xl text-slate-600 leading-relaxed max-w-xl mx-auto lg:mx-0">
                    <?= htmlspecialchars($heroLead ?: 'Crea recetas con tus ingredientes, calcula el costo real de producción —incluyendo merma— y conoce tu margen de ganancia real. Todo en un solo lugar, sin hojas de cálculo que no te cierran.') ?>
                </p>

                <!-- Lead capture form -->
                <?php if ($formSuccess): ?>
                    <div class="mt-8 p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm">
                        <p class="font-semibold">¡Gracias por tu interés!</p>
                        <p>Te contactaremos pronto para activar tu prueba gratuita.</p>
                    </div>
                <?php else: ?>
                    <form method="POST" action="" class="mt-8 max-w-md mx-auto lg:mx-0">
                        <input type="hidden" name="action" value="lead">
                        <!-- Honeypot field — must stay hidden -->
                        <input type="text" name="website" value="" style="position:absolute;left:-9999px;opacity:0;height:0;width:0" tabindex="-1" autocomplete="off">
                        <div class="flex flex-col sm:flex-row gap-3">
                            <input type="email" name="email" required placeholder="Tu correo electrónico"
                                class="flex-1 px-4 py-3 rounded-lg border border-slate-200 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition text-sm">
                            <button type="submit" class="px-6 py-3 rounded-lg bg-brand-600 text-white text-sm font-semibold hover:bg-brand-700 transition-colors shadow-md shadow-brand-600/20 whitespace-nowrap">
                                Pruébalo gratis 14 días
                            </button>
                        </div>
                        <?php if ($formError): ?>
                            <p class="mt-2 text-sm text-red-600"><?= htmlspecialchars($formError) ?></p>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>

                <p class="mt-4 text-sm text-slate-500">Sin tarjeta de crédito · 14 días gratis · Sin compromiso</p>
            </div>

            <!-- Visual Mockup -->
            <div class="relative">
                <div class="bg-white rounded-2xl shadow-2xl shadow-slate-900/10 border border-slate-200/80 p-6 sm:p-8">
                    <!-- Mockup Header -->
                    <div class="flex items-center gap-2 mb-6">
                        <div class="w-3 h-3 rounded-full bg-red-400"></div>
                        <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                        <div class="w-3 h-3 rounded-full bg-green-400"></div>
                        <span class="ml-2 text-xs text-slate-400 font-mono">mimargen.cl/app</span>
                    </div>
                    <!-- Mockup Content -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-slate-900">Pan de masa madre</h3>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">Margen: 38%</span>
                        </div>
                        <div class="border-t border-slate-100 pt-4 space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Materiales</span>
                                <span class="font-medium text-slate-700">$1.200</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Mano de obra</span>
                                <span class="font-medium text-slate-700">$800</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Merma (8%)</span>
                                <span class="font-medium text-slate-700">$150</span>
                            </div>
                            <div class="border-t border-slate-100 pt-3 flex justify-between">
                                <span class="font-bold text-slate-900">Costo total</span>
                                <span class="font-bold text-slate-900">$2.150</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Precio de venta</span>
                                <span class="font-medium text-brand-600">$3.500</span>
                            </div>
                            <div class="bg-green-50 border border-green-200 rounded-lg p-3 text-center">
                                <span class="text-sm font-bold text-green-700">Ganancia: $1.350 por unidad</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Shadow effect -->
                <div class="absolute -bottom-4 -right-4 w-full h-full bg-brand-100/50 rounded-2xl -z-10"></div>
            </div>
        </div>
    </div>
</section>

<!-- ==================== PAIN POINTS ==================== -->
<section id="producto" class="py-16 sm:py-20 lg:py-24 bg-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-12 sm:mb-16">
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">¿Te suena familiar?</h2>
            <p class="mt-4 text-lg text-slate-600">La mayoría de los emprendedores pierden plata sin saberlo. No por falta de esfuerzo, sino por falta de visibilidad.</p>
        </div>
        <div class="grid md:grid-cols-3 gap-6 lg:gap-8">
            <!-- Pain 1 -->
            <div class="rounded-2xl p-6 bg-white border border-slate-200/80 shadow-lg shadow-slate-900/5 text-center hover:shadow-xl transition-shadow">
                <div class="mx-auto w-14 h-14 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center mb-5">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232 1.232 3.229 0 4.461l-.671.671c-1.232 1.232-3.229 1.232-4.461 0L5 10.132" /></svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Pones precios a ojo</h3>
                <p class="text-slate-600 text-sm leading-relaxed">Sumas los ingredientes, le multiplicas por dos y esperas que alcance. Pero nunca sabes si realmente estás ganando o perdiendo plata.</p>
            </div>
            <!-- Pain 2 -->
            <div class="rounded-2xl p-6 bg-white border border-slate-200/80 shadow-lg shadow-slate-900/5 text-center hover:shadow-xl transition-shadow">
                <div class="mx-auto w-14 h-14 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center mb-5">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0112 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0h7.5c.621 0 1.125.504 1.125 1.125M3.375 8.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m17.25-3.75h-7.5c-.621 0-1.125.504-1.125 1.125m8.625-1.125c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125M12 10.875v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125M10.875 12c-.621 0-1.125.504-1.125 1.125M12 12c.621 0 1.125.504 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125m13.5-7.5h-1.5" /></svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Tu Excel ya no da para más</h3>
                <p class="text-slate-600 text-sm leading-relaxed">Tienes diez hojas de cálculo, fórmulas que se rompen solas y cada vez que cambia un precio de insumo, tienes que actualizar todo a mano. Sacar cuentas te toma horas.</p>
            </div>
            <!-- Pain 3 -->
            <div class="rounded-2xl p-6 bg-white border border-slate-200/80 shadow-lg shadow-slate-900/5 text-center hover:shadow-xl transition-shadow">
                <div class="mx-auto w-14 h-14 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center mb-5">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">No sabes cuánto stock te queda</h3>
                <p class="text-slate-600 text-sm leading-relaxed">Aceptas un pedido grande y recién al otro día te das cuenta de que no tienes suficiente materia prima. El stock lo llevas en la cabeza o en un cuaderno.</p>
            </div>
        </div>
    </div>
</section>

<!-- ==================== HOW IT WORKS ==================== -->
<section id="como-funciona" class="py-16 sm:py-20 lg:py-24 bg-cream-50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-12 sm:mb-16">
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Tres pasos para conocer tu ganancia real</h2>
            <p class="mt-4 text-lg text-slate-600">Sin complicaciones. Sin hojas de cálculo. Sin contabilidad avanzada.</p>
        </div>
        <div class="grid md:grid-cols-3 gap-8 lg:gap-12">
            <!-- Step 1 -->
            <div class="relative text-center">
                <div class="mx-auto w-16 h-16 rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 text-white flex items-center justify-center text-xl font-extrabold shadow-lg shadow-brand-500/25 mb-6">01</div>
                <div class="hidden md:block absolute top-8 left-2/3 w-1/3 border-t-2 border-dashed border-brand-200"></div>
                <div class="mx-auto w-10 h-10 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Crea tu receta</h3>
                <p class="text-slate-600 text-sm leading-relaxed max-w-xs mx-auto">Agrega los ingredientes o materiales que usas, con sus cantidades exactas. Puedes usar productos de tu inventario o crear insumos nuevos.</p>
            </div>
            <!-- Step 2 -->
            <div class="relative text-center">
                <div class="mx-auto w-16 h-16 rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 text-white flex items-center justify-center text-xl font-extrabold shadow-lg shadow-brand-500/25 mb-6">02</div>
                <div class="hidden md:block absolute top-8 left-2/3 w-1/3 border-t-2 border-dashed border-brand-200"></div>
                <div class="mx-auto w-10 h-10 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Calcula el costo real</h3>
                <p class="text-slate-600 text-sm leading-relaxed max-w-xs mx-auto">MiMargen suma automáticamente el costo de materiales, mano de obra, gastos fijos y la merma que generas en el proceso. Sin fórmulas, sin errores.</p>
            </div>
            <!-- Step 3 -->
            <div class="relative text-center">
                <div class="mx-auto w-16 h-16 rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 text-white flex items-center justify-center text-xl font-extrabold shadow-lg shadow-brand-500/25 mb-6">03</div>
                <div class="mx-auto w-10 h-10 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Conoce tu margen</h3>
                <p class="text-slate-600 text-sm leading-relaxed max-w-xs mx-auto">Pon tu precio de venta y ve al instante cuánto ganas realmente por cada producto. Si el margen no te cierra, ajustas y recalculas en segundos.</p>
            </div>
        </div>
    </div>
</section>

<!-- ==================== FEATURES ==================== -->
<section id="features" class="py-16 sm:py-20 lg:py-24 bg-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-12 sm:mb-16">
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Más que costos. Tu negocio entero.</h2>
            <p class="mt-4 text-lg text-slate-600">Todo lo que otros ERPs no tienen en un solo lugar.</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Feature 1 -->
            <div class="rounded-2xl p-6 bg-white border border-slate-200/80 shadow-lg shadow-slate-900/5 hover:shadow-xl transition-shadow group">
                <div class="w-10 h-10 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center mb-4 group-hover:bg-brand-100 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                </div>
                <h3 class="text-base font-bold text-slate-900 mb-1">Costeo por receta</h3>
                <p class="text-slate-600 text-sm leading-relaxed">Calcula el costo real de cada producto incluyendo materiales, mano de obra, merma y gastos fijos.</p>
            </div>
            <!-- Feature 2 -->
            <div class="rounded-2xl p-6 bg-white border border-slate-200/80 shadow-lg shadow-slate-900/5 hover:shadow-xl transition-shadow group">
                <div class="w-10 h-10 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center mb-4 group-hover:bg-brand-100 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                </div>
                <h3 class="text-base font-bold text-slate-900 mb-1">Inventario en tiempo real</h3>
                <p class="text-slate-600 text-sm leading-relaxed">Cada vez que produces o vendes, tu stock se actualiza solo. Alertas cuando algo está por agotarse.</p>
            </div>
            <!-- Feature 3 -->
            <div class="rounded-2xl p-6 bg-white border border-slate-200/80 shadow-lg shadow-slate-900/5 hover:shadow-xl transition-shadow group">
                <div class="w-10 h-10 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center mb-4 group-hover:bg-brand-100 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                </div>
                <h3 class="text-base font-bold text-slate-900 mb-1">Gestión de ventas</h3>
                <p class="text-slate-600 text-sm leading-relaxed">Cotizaciones, órdenes de venta y seguimiento de clientes. Todo conectado a tu inventario.</p>
            </div>
            <!-- Feature 4 -->
            <div class="rounded-2xl p-6 bg-white border border-slate-200/80 shadow-lg shadow-slate-900/5 hover:shadow-xl transition-shadow group">
                <div class="w-10 h-10 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center mb-4 group-hover:bg-brand-100 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" /></svg>
                </div>
                <h3 class="text-base font-bold text-slate-900 mb-1">Facturación electrónica</h3>
                <p class="text-slate-600 text-sm leading-relaxed">Emite boletas, facturas y guías de despacho directamente desde MiMargen. Integrado con el SII de Chile.</p>
            </div>
            <!-- Feature 5 -->
            <div class="rounded-2xl p-6 bg-white border border-slate-200/80 shadow-lg shadow-slate-900/5 hover:shadow-xl transition-shadow group">
                <div class="w-10 h-10 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center mb-4 group-hover:bg-brand-100 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <h3 class="text-base font-bold text-slate-900 mb-1">Control de caja</h3>
                <p class="text-slate-600 text-sm leading-relaxed">Gestiona tus cuentas bancarias, registra ingresos y egresos, y mira tu flujo de caja en un vistazo.</p>
            </div>
            <!-- Feature 6 -->
            <div class="rounded-2xl p-6 bg-white border border-slate-200/80 shadow-lg shadow-slate-900/5 hover:shadow-xl transition-shadow group">
                <div class="w-10 h-10 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center mb-4 group-hover:bg-brand-100 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                </div>
                <h3 class="text-base font-bold text-slate-900 mb-1">Reportes claros</h3>
                <p class="text-slate-600 text-sm leading-relaxed">Rentabilidad por producto, productos más vendidos y evolución de costos. Sin contabilidad avanzada.</p>
            </div>
        </div>

        <!-- Comparison Table -->
        <div class="mt-16 max-w-3xl mx-auto">
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-lg shadow-slate-900/5 overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50">
                            <th class="text-left py-4 px-5 font-bold text-slate-900"></th>
                            <th class="text-center py-4 px-5 font-bold text-brand-600">MiMargen</th>
                            <th class="text-center py-4 px-5 font-bold text-slate-500">Excel</th>
                            <th class="text-center py-4 px-5 font-bold text-slate-500">ERPs tradicionales</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr>
                            <td class="py-3 px-5 text-slate-700 font-medium">Costeo por receta</td>
                            <td class="py-3 px-5 text-center text-green-600 font-bold">✅ Incluido</td>
                            <td class="py-3 px-5 text-center text-slate-400">Fórmulas manuales</td>
                            <td class="py-3 px-5 text-center text-red-400">No existe</td>
                        </tr>
                        <tr>
                            <td class="py-3 px-5 text-slate-700 font-medium">Facturación electrónica</td>
                            <td class="py-3 px-5 text-center text-green-600 font-bold">✅ Integrada</td>
                            <td class="py-3 px-5 text-center text-slate-400">Por separado</td>
                            <td class="py-3 px-5 text-center text-yellow-500">A veces</td>
                        </tr>
                        <tr>
                            <td class="py-3 px-5 text-slate-700 font-medium">Inventario automático</td>
                            <td class="py-3 px-5 text-center text-green-600 font-bold">En tiempo real</td>
                            <td class="py-3 px-5 text-center text-slate-400">Manual</td>
                            <td class="py-3 px-5 text-center text-yellow-500">Complejo</td>
                        </tr>
                        <tr>
                            <td class="py-3 px-5 text-slate-700 font-medium">Curva de aprendizaje</td>
                            <td class="py-3 px-5 text-center text-green-600 font-bold">Horas</td>
                            <td class="py-3 px-5 text-center text-slate-400">—</td>
                            <td class="py-3 px-5 text-center text-red-400">Semanas</td>
                        </tr>
                        <tr>
                            <td class="py-3 px-5 text-slate-700 font-medium">Precio accesible</td>
                            <td class="py-3 px-5 text-center text-green-600 font-bold">Desde $29.990/mes</td>
                            <td class="py-3 px-5 text-center text-slate-400">"Gratis"</td>
                            <td class="py-3 px-5 text-center text-red-400">$200.000+/mes</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- ==================== WHO IT IS FOR ==================== -->
<section id="para-quien" class="py-16 sm:py-20 lg:py-24 bg-cream-50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-12 sm:mb-16">
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Hecho para quienes producen</h2>
            <p class="mt-4 text-lg text-slate-600">Si transformas materia prima en producto terminado, MiMargen es para ti.</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Industry 1 -->
            <div class="rounded-2xl p-6 bg-white border border-slate-200/80 shadow-lg shadow-slate-900/5 hover:shadow-xl transition-shadow">
                <div class="text-3xl mb-4">🍞</div>
                <h3 class="text-base font-bold text-slate-900 mb-2">Panaderías y pastelerías</h3>
                <p class="text-slate-600 text-sm leading-relaxed">Calcula el costo exacto de cada receta — desde un pan de masa madre hasta una torta de tres pisos.</p>
            </div>
            <!-- Industry 2 -->
            <div class="rounded-2xl p-6 bg-white border border-slate-200/80 shadow-lg shadow-slate-900/5 hover:shadow-xl transition-shadow">
                <div class="text-3xl mb-4">☕</div>
                <h3 class="text-base font-bold text-slate-900 mb-2">Tostadores de café</h3>
                <p class="text-slate-600 text-sm leading-relaxed">Sabe cuánto te cuesta realmente cada bolsa después de la merma del tostado y el empaque.</p>
            </div>
            <!-- Industry 3 -->
            <div class="rounded-2xl p-6 bg-white border border-slate-200/80 shadow-lg shadow-slate-900/5 hover:shadow-xl transition-shadow">
                <div class="text-3xl mb-4">🧴</div>
                <h3 class="text-base font-bold text-slate-900 mb-2">Cosmética natural</h3>
                <p class="text-slate-600 text-sm leading-relaxed">Controla las proporciones de cada fórmula y el costo real por frasco, crema o jabón.</p>
            </div>
            <!-- Industry 4 -->
            <div class="rounded-2xl p-6 bg-white border border-slate-200/80 shadow-lg shadow-slate-900/5 hover:shadow-xl transition-shadow">
                <div class="text-3xl mb-4">🍫</div>
                <h3 class="text-base font-bold text-slate-900 mb-2">Chocolaterías y confitería</h3>
                <p class="text-slate-600 text-sm leading-relaxed">Cada gramo de cacao cuenta. Costea con precisión y ajusta tus precios sin perder clientes.</p>
            </div>
            <!-- Industry 5 -->
            <div class="rounded-2xl p-6 bg-white border border-slate-200/80 shadow-lg shadow-slate-900/5 hover:shadow-xl transition-shadow">
                <div class="text-3xl mb-4">🧀</div>
                <h3 class="text-base font-bold text-slate-900 mb-2">Alimentos artesanales</h3>
                <p class="text-slate-600 text-sm leading-relaxed">Desde mermeladas hasta quesos — sabe cuánto te cuesta producir y cuánto realmente ganas.</p>
            </div>
            <!-- Industry 6 -->
            <div class="rounded-2xl p-6 bg-white border border-slate-200/80 shadow-lg shadow-slate-900/5 hover:shadow-xl transition-shadow">
                <div class="text-3xl mb-4">🏭</div>
                <h3 class="text-base font-bold text-slate-900 mb-2">Pequeñas fábricas</h3>
                <p class="text-slate-600 text-sm leading-relaxed">Si transformas materia prima en producto terminado, MiMargen te muestra tu margen real.</p>
            </div>
        </div>
    </div>
</section>

<!-- ==================== CALCULATOR ==================== -->
<section id="calculadora" class="py-16 sm:py-20 lg:py-24 bg-cream-50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-10 sm:mb-14">
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Calcula el costo de tu receta en 90 segundos</h2>
            <p class="mt-4 text-lg text-slate-600">No necesitas registrarte ni dar tu email. Ingresa tus ingredientes, cantidades y precios — y la calculadora te devuelve el costo total, el costo por unidad y el margen de ganancia sugerido.</p>
        </div>
        <div class="max-w-2xl mx-auto">
            <div id="calculator-widget" class="bg-white rounded-2xl border border-slate-200/80 shadow-xl shadow-slate-900/5 p-6 sm:p-8">
                <h3 class="text-xl font-bold text-slate-900 mb-2">Calcula el costo de tu receta</h3>
                <p class="text-sm text-slate-500 mb-6">Ingresa tus ingredientes y obtén el costo real al instante.</p>

                <!-- Rate Limit Notice -->
                <div id="rate-limit-notice" class="hidden mb-6 p-4 rounded-xl bg-amber-50 border border-amber-200">
                    <p class="text-sm text-amber-800 font-medium">Has alcanzado el límite de cálculos gratuitos por hoy.</p>
                    <p class="text-xs text-amber-600 mt-1">
                        <a href="#precios" class="underline font-semibold hover:text-amber-900">Prueba MiMargen gratis</a> para cálculos ilimitados.
                    </p>
                </div>

                <!-- Usage Counter -->
                <div id="usage-counter" class="text-xs text-slate-400 mb-4">
                    Cálculos restantes hoy: <span id="remaining-count" class="font-bold text-slate-600">3</span>
                </div>

                <!-- Ingredients -->
                <div id="ingredients-list" class="space-y-3 mb-6">
                    <div class="ingredient-row grid grid-cols-12 gap-2 items-center">
                        <div class="col-span-5">
                            <label class="block text-xs font-medium text-slate-500 mb-1">Ingrediente</label>
                            <input type="text" placeholder="Ej: Harina" class="ing-name w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition" />
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-slate-500 mb-1">Cantidad</label>
                            <input type="number" placeholder="500" min="0" step="any" class="ing-qty w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition" />
                        </div>
                        <div class="col-span-3">
                            <label class="block text-xs font-medium text-slate-500 mb-1">Precio unit.</label>
                            <input type="number" placeholder="1.200" min="0" step="any" class="ing-price w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition" />
                        </div>
                        <div class="col-span-2 flex items-end">
                            <button type="button" class="remove-ing hidden w-8 h-8 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 transition flex items-center justify-center" aria-label="Eliminar ingrediente">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <button type="button" id="add-ingredient" class="text-sm text-brand-600 hover:text-brand-700 font-medium mb-6 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Agregar ingrediente
                </button>

                <!-- Additional Inputs -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Mano de obra ($/hora)</label>
                        <input type="number" id="labor-cost" placeholder="4.200" min="0" class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Horas por lote</label>
                        <input type="number" id="labor-hours" placeholder="1" min="0" step="0.5" class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Merma (%)</label>
                        <input type="number" id="waste-pct" placeholder="8" min="0" max="100" class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Unidades producidas</label>
                        <input type="number" id="units" placeholder="10" min="1" class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition" />
                    </div>
                </div>

                <!-- Calculate Button -->
                <button type="button" id="calculate-btn" class="w-full py-3 rounded-xl bg-brand-600 text-white font-semibold hover:bg-brand-700 transition-colors shadow-md shadow-brand-600/20">
                    Calcular costo
                </button>

                <!-- Results -->
                <div id="results" class="hidden mt-6 p-5 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Materiales</span>
                        <span id="res-materials" class="font-medium text-slate-700">$0</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Mano de obra</span>
                        <span id="res-labor" class="font-medium text-slate-700">$0</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Merma</span>
                        <span id="res-waste" class="font-medium text-slate-700">$0</span>
                    </div>
                    <div class="border-t border-slate-200 pt-3 flex justify-between">
                        <span class="font-bold text-slate-900">Costo total</span>
                        <span id="res-total" class="font-bold text-slate-900">$0</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Costo por unidad</span>
                        <span id="res-unit" class="font-medium text-brand-600">$0</span>
                    </div>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-3 text-center">
                        <span class="text-sm font-bold text-green-700" id="res-margin">Margen sugerido (50%): $0 por unidad</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==================== TESTIMONIALS ==================== -->
<section id="testimonios" class="py-16 sm:py-20 lg:py-24 bg-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-12 sm:mb-16">
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Lo que dicen quienes ya lo usan</h2>
        </div>
        <div class="grid md:grid-cols-3 gap-6 lg:gap-8">
            <!-- Testimonial 1 -->
            <div class="rounded-2xl p-6 bg-white border border-slate-200/80 shadow-lg shadow-slate-900/5 flex flex-col">
                <div class="flex-1">
                    <div class="flex items-center gap-1 mb-4">
                        <span class="text-yellow-400 text-sm">★</span>
                        <span class="text-yellow-400 text-sm">★</span>
                        <span class="text-yellow-400 text-sm">★</span>
                        <span class="text-yellow-400 text-sm">★</span>
                        <span class="text-yellow-400 text-sm">★</span>
                    </div>
                    <blockquote class="text-slate-700 text-sm leading-relaxed mb-6">"Antes pensaba que ganaba $500 por pan. Con MiMargen descubrí que, con la merma y el tiempo de amasado, ganaba $120. Ajusté precios y ahora mi margen real es del 34%. Ojalá lo hubiera usado antes."</blockquote>
                </div>
                <div class="border-t border-slate-100 pt-4 flex items-center justify-between">
                    <div>
                        <p class="font-bold text-slate-900 text-sm">Carolina Muñoz</p>
                        <p class="text-slate-500 text-xs">Panadería artesanal, Santiago</p>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">Margen real: 34%</span>
                </div>
            </div>
            <!-- Testimonial 2 -->
            <div class="rounded-2xl p-6 bg-white border border-slate-200/80 shadow-lg shadow-slate-900/5 flex flex-col">
                <div class="flex-1">
                    <div class="flex items-center gap-1 mb-4">
                        <span class="text-yellow-400 text-sm">★</span>
                        <span class="text-yellow-400 text-sm">★</span>
                        <span class="text-yellow-400 text-sm">★</span>
                        <span class="text-yellow-400 text-sm">★</span>
                        <span class="text-yellow-400 text-sm">★</span>
                    </div>
                    <blockquote class="text-slate-700 text-sm leading-relaxed mb-6">"Perdía plata en tres de mis blends y no tenía idea. MiMargen me mostró exactamente cuáles y por qué. En dos semanas ya había corregido los precios. Hoy facturo un 22% más con el mismo volumen."</blockquote>
                </div>
                <div class="border-t border-slate-100 pt-4 flex items-center justify-between">
                    <div>
                        <p class="font-bold text-slate-900 text-sm">Diego Aravena</p>
                        <p class="text-slate-500 text-xs">Tostador de café, Valparaíso</p>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">+22% facturación</span>
                </div>
            </div>
            <!-- Testimonial 3 -->
            <div class="rounded-2xl p-6 bg-white border border-slate-200/80 shadow-lg shadow-slate-900/5 flex flex-col">
                <div class="flex-1">
                    <div class="flex items-center gap-1 mb-4">
                        <span class="text-yellow-400 text-sm">★</span>
                        <span class="text-yellow-400 text-sm">★</span>
                        <span class="text-yellow-400 text-sm">★</span>
                        <span class="text-yellow-400 text-sm">★</span>
                        <span class="text-yellow-400 text-sm">★</span>
                    </div>
                    <blockquote class="text-slate-700 text-sm leading-relaxed mb-6">"Tenía todo en Excel y era un desastre. Ahora creo la receta, pongo el precio y veo mi margen al instante. Además, las facturas electrónicas me salen directo desde ahí. Me ahorré contratar a alguien más para eso."</blockquote>
                </div>
                <div class="border-t border-slate-100 pt-4 flex items-center justify-between">
                    <div>
                        <p class="font-bold text-slate-900 text-sm">Francisca López</p>
                        <p class="text-slate-500 text-xs">Cosmética natural, Concepción</p>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">Sin Excel</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==================== PRICING ==================== -->
<section id="precios" class="py-16 sm:py-20 lg:py-24 bg-cream-50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-12 sm:mb-16">
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Planes que crecen con tu negocio</h2>
            <p class="mt-4 text-lg text-slate-600">Todos los planes incluyen 14 días gratis. Sin tarjeta de crédito. Sin compromiso.</p>
        </div>
        <div class="grid md:grid-cols-3 gap-6 lg:gap-8 max-w-5xl mx-auto">
            <!-- Plan: Emprendedor -->
            <div class="rounded-2xl p-6 bg-white border border-slate-200/80 hover:shadow-lg transition-shadow">
                <div class="text-center mb-6">
                    <h3 class="text-lg font-bold text-slate-900">Emprendedor</h3>
                    <div class="mt-3">
                        <span class="text-4xl font-extrabold text-slate-900">$29.990</span>
                        <span class="text-slate-500 text-sm">/mes</span>
                    </div>
                    <p class="text-slate-400 text-xs mt-1">~0,8 UF</p>
                </div>
                <ul class="space-y-3 mb-8">
                    <li class="flex items-start gap-2 text-sm text-slate-700">
                        <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Hasta 50 recetas
                    </li>
                    <li class="flex items-start gap-2 text-sm text-slate-700">
                        <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        1 usuario
                    </li>
                    <li class="flex items-start gap-2 text-sm text-slate-700">
                        <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Inventario básico
                    </li>
                    <li class="flex items-start gap-2 text-sm text-slate-700">
                        <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Calculadora de costos
                    </li>
                    <li class="flex items-start gap-2 text-sm text-slate-700">
                        <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Soporte por email
                    </li>
                </ul>
                <a href="#precios" class="inline-flex items-center justify-center w-full px-5 py-2.5 rounded-lg bg-white text-slate-700 border border-slate-200 hover:bg-slate-50 hover:border-slate-300 font-semibold text-sm transition-all duration-150 active:scale-[0.97]">Empezar gratis</a>
            </div>
            <!-- Plan: Productor (popular) -->
            <div class="rounded-2xl p-6 bg-white border border-slate-200/80 shadow-lg shadow-slate-900/5 relative ring-2 ring-brand-500 shadow-xl shadow-brand-500/10">
                <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-brand-600 text-white shadow-md">Más popular</span>
                </div>
                <div class="text-center mb-6">
                    <h3 class="text-lg font-bold text-slate-900">Productor</h3>
                    <div class="mt-3">
                        <span class="text-4xl font-extrabold text-slate-900">$59.990</span>
                        <span class="text-slate-500 text-sm">/mes</span>
                    </div>
                    <p class="text-slate-400 text-xs mt-1">~1,6 UF</p>
                </div>
                <ul class="space-y-3 mb-8">
                    <li class="flex items-start gap-2 text-sm text-slate-700">
                        <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Recetas ilimitadas
                    </li>
                    <li class="flex items-start gap-2 text-sm text-slate-700">
                        <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Hasta 3 usuarios
                    </li>
                    <li class="flex items-start gap-2 text-sm text-slate-700">
                        <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Inventario avanzado con alertas
                    </li>
                    <li class="flex items-start gap-2 text-sm text-slate-700">
                        <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Facturación electrónica (DTE)
                    </li>
                    <li class="flex items-start gap-2 text-sm text-slate-700">
                        <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Gestión de ventas
                    </li>
                    <li class="flex items-start gap-2 text-sm text-slate-700">
                        <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Reportes de rentabilidad
                    </li>
                    <li class="flex items-start gap-2 text-sm text-slate-700">
                        <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Soporte prioritario
                    </li>
                </ul>
                <a href="#precios" class="inline-flex items-center justify-center w-full px-6 py-3 rounded-lg bg-brand-600 text-white font-semibold text-base hover:bg-brand-700 shadow-md shadow-brand-600/20 hover:shadow-brand-600/30 transition-all duration-150 active:scale-[0.97]">Empezar gratis</a>
            </div>
            <!-- Plan: Empresa -->
            <div class="rounded-2xl p-6 bg-white border border-slate-200/80 hover:shadow-lg transition-shadow">
                <div class="text-center mb-6">
                    <h3 class="text-lg font-bold text-slate-900">Empresa</h3>
                    <div class="mt-3">
                        <span class="text-4xl font-extrabold text-slate-900">$99.990</span>
                        <span class="text-slate-500 text-sm">/mes</span>
                    </div>
                    <p class="text-slate-400 text-xs mt-1">~2,7 UF</p>
                </div>
                <ul class="space-y-3 mb-8">
                    <li class="flex items-start gap-2 text-sm text-slate-700">
                        <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Todo del plan Productor
                    </li>
                    <li class="flex items-start gap-2 text-sm text-slate-700">
                        <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Usuarios ilimitados
                    </li>
                    <li class="flex items-start gap-2 text-sm text-slate-700">
                        <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Control de caja y cuentas bancarias
                    </li>
                    <li class="flex items-start gap-2 text-sm text-slate-700">
                        <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Múltiples bodegas
                    </li>
                    <li class="flex items-start gap-2 text-sm text-slate-700">
                        <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        API de integración
                    </li>
                    <li class="flex items-start gap-2 text-sm text-slate-700">
                        <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Soporte dedicado
                    </li>
                    <li class="flex items-start gap-2 text-sm text-slate-700">
                        <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Onboarding personalizado
                    </li>
                </ul>
                <a href="#precios" class="inline-flex items-center justify-center w-full px-5 py-2.5 rounded-lg bg-white text-slate-700 border border-slate-200 hover:bg-slate-50 hover:border-slate-300 font-semibold text-sm transition-all duration-150 active:scale-[0.97]">Hablar con ventas</a>
            </div>
        </div>
        <p class="text-center text-xs text-slate-400 mt-8">Precios en pesos chilenos, sujetos a variación UF.</p>
    </div>
</section>

<!-- ==================== FAQ ==================== -->
<section id="faq" class="py-16 sm:py-20 lg:py-24 bg-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-12 sm:mb-16">
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Preguntas frecuentes</h2>
        </div>
        <div class="max-w-3xl mx-auto space-y-3">
            <!-- FAQ 1 -->
            <details class="group bg-white border border-slate-200/80 rounded-xl overflow-hidden open:shadow-md transition-shadow">
                <summary class="flex items-center justify-between cursor-pointer p-5 text-base font-semibold text-slate-900 hover:text-brand-600 transition-colors list-none">
                    ¿Qué es MiMargen y para qué sirve?
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform shrink-0 ml-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </summary>
                <div class="px-5 pb-5 text-sm text-slate-600 leading-relaxed">
                    MiMargen es un software de gestión para pequeños productores que te permite calcular el costo real de tus productos usando recetas, controlar tu inventario, gestionar ventas y emitir facturación electrónica. Todo en un solo lugar.
                </div>
            </details>
            <!-- FAQ 2 -->
            <details class="group bg-white border border-slate-200/80 rounded-xl overflow-hidden open:shadow-md transition-shadow">
                <summary class="flex items-center justify-between cursor-pointer p-5 text-base font-semibold text-slate-900 hover:text-brand-600 transition-colors list-none">
                    ¿Cómo se calcula el costo de producción con recetas?
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform shrink-0 ml-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </summary>
                <div class="px-5 pb-5 text-sm text-slate-600 leading-relaxed">
                    Creas una receta con los ingredientes y cantidades que usas. MiMargen toma el costo de cada insumo de tu inventario, le suma la mano de obra, los gastos fijos que asignes y la merma del proceso. El resultado es el costo real por unidad producida.
                </div>
            </details>
            <!-- FAQ 3 -->
            <details class="group bg-white border border-slate-200/80 rounded-xl overflow-hidden open:shadow-md transition-shadow">
                <summary class="flex items-center justify-between cursor-pointer p-5 text-base font-semibold text-slate-900 hover:text-brand-600 transition-colors list-none">
                    ¿MiMargen sirve si no hago alimentos?
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform shrink-0 ml-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </summary>
                <div class="px-5 pb-5 text-sm text-slate-600 leading-relaxed">
                    Sí. Aunque el concepto de "receta" viene del mundo gastronómico, funciona para cualquier producto que se fabrique combinando materiales: cosméticos, velas, muebles, textiles. Si transformas insumos en un producto terminado, MiMargen te sirve.
                </div>
            </details>
            <!-- FAQ 4 -->
            <details class="group bg-white border border-slate-200/80 rounded-xl overflow-hidden open:shadow-md transition-shadow">
                <summary class="flex items-center justify-between cursor-pointer p-5 text-base font-semibold text-slate-900 hover:text-brand-600 transition-colors list-none">
                    ¿Puedo emitir facturas electrónicas en Chile?
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform shrink-0 ml-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </summary>
                <div class="px-5 pb-5 text-sm text-slate-600 leading-relaxed">
                    Sí. MiMargen está integrado con el SII y te permite emitir boletas, facturas y guías de despacho electrónicas directamente desde la plataforma. No necesitas otro software para cumplir con tus obligaciones tributarias.
                </div>
            </details>
            <!-- FAQ 5 -->
            <details class="group bg-white border border-slate-200/80 rounded-xl overflow-hidden open:shadow-md transition-shadow">
                <summary class="flex items-center justify-between cursor-pointer p-5 text-base font-semibold text-slate-900 hover:text-brand-600 transition-colors list-none">
                    ¿Tiene período de prueba gratis?
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform shrink-0 ml-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </summary>
                <div class="px-5 pb-5 text-sm text-slate-600 leading-relaxed">
                    Sí. Todos los planes incluyen 14 días de prueba gratis, sin necesidad de ingresar tarjeta de crédito. Puedes usar todas las funciones del plan que elijas durante la prueba.
                </div>
            </details>
            <!-- FAQ 6 -->
            <details class="group bg-white border border-slate-200/80 rounded-xl overflow-hidden open:shadow-md transition-shadow">
                <summary class="flex items-center justify-between cursor-pointer p-5 text-base font-semibold text-slate-900 hover:text-brand-600 transition-colors list-none">
                    ¿Puedo migrar mis datos desde Excel?
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform shrink-0 ml-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </summary>
                <div class="px-5 pb-5 text-sm text-slate-600 leading-relaxed">
                    Sí. MiMargen permite importar tu inventario, clientes y productos desde archivos CSV o Excel. Además, nuestro equipo de soporte te ayuda en el proceso para que no pierdas nada.
                </div>
            </details>
            <!-- FAQ 7 -->
            <details class="group bg-white border border-slate-200/80 rounded-xl overflow-hidden open:shadow-md transition-shadow">
                <summary class="flex items-center justify-between cursor-pointer p-5 text-base font-semibold text-slate-900 hover:text-brand-600 transition-colors list-none">
                    ¿Qué es la merma y por qué importa?
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform shrink-0 ml-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </summary>
                <div class="px-5 pb-5 text-sm text-slate-600 leading-relaxed">
                    La merma es el material que se pierde durante la producción — lo que se evapora, se quema, se derrama o queda en los recipientes. Si no la consideras, tu costo real siempre va a ser mayor al que calculas. MiMargen te permite asignar un porcentaje de merma por receta para que el costo sea preciso.
                </div>
            </details>
            <!-- FAQ 8 -->
            <details class="group bg-white border border-slate-200/80 rounded-xl overflow-hidden open:shadow-md transition-shadow">
                <summary class="flex items-center justify-between cursor-pointer p-5 text-base font-semibold text-slate-900 hover:text-brand-600 transition-colors list-none">
                    ¿MiMargen funciona para negocios fuera de Chile?
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform shrink-0 ml-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </summary>
                <div class="px-5 pb-5 text-sm text-slate-600 leading-relaxed">
                    La funcionalidad de costeo por receta, inventario y ventas funciona en cualquier país. La facturación electrónica está adaptada al SII de Chile, pero estamos trabajando en integraciones para otros países de Latinoamérica.
                </div>
            </details>
            <!-- FAQ 9 -->
            <details class="group bg-white border border-slate-200/80 rounded-xl overflow-hidden open:shadow-md transition-shadow">
                <summary class="flex items-center justify-between cursor-pointer p-5 text-base font-semibold text-slate-900 hover:text-brand-600 transition-colors list-none">
                    ¿Necesito saber de contabilidad para usarlo?
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform shrink-0 ml-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </summary>
                <div class="px-5 pb-5 text-sm text-slate-600 leading-relaxed">
                    No. MiMargen está diseñado para productores, no para contadores. Si sabes cuánto te cuesta cada ingrediente y cuánto tiempo te lleva producir, el sistema hace el resto. Los reportes son claros y en lenguaje simple.
                </div>
            </details>
            <!-- FAQ 10 -->
            <details class="group bg-white border border-slate-200/80 rounded-xl overflow-hidden open:shadow-md transition-shadow">
                <summary class="flex items-center justify-between cursor-pointer p-5 text-base font-semibold text-slate-900 hover:text-brand-600 transition-colors list-none">
                    ¿Puedo cambiar de plan en cualquier momento?
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform shrink-0 ml-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </summary>
                <div class="px-5 pb-5 text-sm text-slate-600 leading-relaxed">
                    Sí. Puedes subir o bajar de plan cuando quieras. El cambio se aplica de forma proporcional y no pierdes ninguno de tus datos.
                </div>
            </details>
        </div>
    </div>
</section>

<!-- ==================== FINAL CTA ==================== -->
<section class="py-16 sm:py-20 lg:py-24 bg-slate-900 text-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto">
            <h2 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                Deja de adivinar. <span class="text-brand-400">Empieza a saber.</span>
            </h2>
            <p class="mt-4 text-lg text-slate-300 leading-relaxed">
                Cada día que pasas sin conocer tu margen real es un día que puedes estar perdiendo plata. Prueba MiMargen gratis durante 14 días y conoce tu ganancia de verdad.
            </p>
            <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
                <a href="#precios" class="inline-flex items-center justify-center px-6 py-3 rounded-lg bg-brand-500 text-white text-base font-semibold hover:bg-brand-400 shadow-md shadow-brand-500/30 transition-all duration-150 active:scale-[0.97]">
                    Empezar gratis — sin tarjeta
                </a>
                <a href="https://wa.me/<?= htmlspecialchars($contactWhatsApp) ?>" class="inline-flex items-center justify-center px-5 py-2.5 rounded-lg border border-slate-600 text-white text-sm font-semibold hover:bg-slate-800 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    Hablar por WhatsApp
                </a>
            </div>
        </div>
    </div>
</section>
</main>

<!-- ==================== FOOTER ==================== -->
<footer class="bg-slate-900 text-slate-400">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Brand -->
            <div class="md:col-span-2">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <span class="text-lg font-bold text-white">Mi<span class="text-brand-400">Margen</span></span>
                </div>
                <p class="text-sm text-slate-400 max-w-xs">
                    Conoce el costo real de cada producto que fabricas. Calcula tu margen de ganancia de verdad.
                </p>
                <div class="flex items-center gap-4 mt-6">
                    <a href="<?= htmlspecialchars($socialInstagram) ?>" class="text-slate-400 hover:text-brand-400 transition-colors" aria-label="Instagram">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                    </a>
                    <a href="<?= htmlspecialchars($socialLinkedin) ?>" class="text-slate-400 hover:text-brand-400 transition-colors" aria-label="LinkedIn">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    </a>
                    <a href="https://youtube.com/@mimargen" class="text-slate-400 hover:text-brand-400 transition-colors" aria-label="YouTube">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                    </a>
                </div>
            </div>

            <!-- Navigation -->
            <div>
                <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Producto</h3>
                <ul class="space-y-3">
                    <li><a href="#producto" class="text-sm hover:text-brand-400 transition-colors">Producto</a></li>
                    <li><a href="#precios" class="text-sm hover:text-brand-400 transition-colors">Precios</a></li>
                    <li><a href="#calculadora" class="text-sm hover:text-brand-400 transition-colors">Calculadora gratuita</a></li>
                    <li><a href="mailto:<?= htmlspecialchars($contactEmail) ?>" class="text-sm hover:text-brand-400 transition-colors">Soporte</a></li>
                </ul>
            </div>

            <!-- Legal -->
            <div>
                <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Legal</h3>
                <ul class="space-y-3">
                    <li><a href="/terminos" class="text-sm hover:text-brand-400 transition-colors">Términos y condiciones</a></li>
                    <li><a href="/privacidad" class="text-sm hover:text-brand-400 transition-colors">Política de privacidad</a></li>
                    <li><a href="/cookies" class="text-sm hover:text-brand-400 transition-colors">Política de cookies</a></li>
                </ul>
                <div class="mt-6">
                    <p class="text-sm">
                        <a href="mailto:<?= htmlspecialchars($contactEmail) ?>" class="text-brand-400 hover:text-brand-300 transition-colors"><?= htmlspecialchars($contactEmail) ?></a>
                    </p>
                    <?php if ($contactWhatsApp): ?>
                    <p class="text-sm mt-1">
                        <a href="https://wa.me/<?= htmlspecialchars($contactWhatsApp) ?>" class="text-brand-400 hover:text-brand-300 transition-colors">WhatsApp</a>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-800 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-xs text-slate-500">&copy; <?= $year ?> MiMargen. Todos los derechos reservados. Hecho en Chile.</p>
        </div>
    </div>
</footer>

<!-- ==================== SCRIPTS ==================== -->
<script>
    // Mobile menu toggle
    (function() {
        var btn = document.getElementById('mobile-menu-btn');
        var menu = document.getElementById('mobile-menu');
        if (btn && menu) {
            btn.addEventListener('click', function() {
                menu.classList.toggle('hidden');
            });
            menu.querySelectorAll('a').forEach(function(link) {
                link.addEventListener('click', function() {
                    menu.classList.add('hidden');
                });
            });
        }
    })();
</script>
<script src="/assets/calculator.js" defer></script>

</body>
</html>
