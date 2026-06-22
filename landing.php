<?php
/**
 * MiMargen Landing Page
 *
 * Single-file PHP entry point for the apex domain (mimargen.cl).
 * Bootstraps the standalone PHP backend, injects PlatformSettings variables,
 * generates canonical/OG meta tags, and handles honeypot lead-capture.
 *
 * NOTE: This is the standalone deployment build. It mirrors the design and
 * content of OtterErp's public/landing.php but keeps the JSON storage backend
 * (core/JsonStorage.php) instead of SqliteStorage, per DEPLOY.md contract.
 */

require_once __DIR__ . '/core/bootstrap.php';

$appName    = defined('APP_NAME') ? APP_NAME : 'MiMargen';
$baseDomain = defined('BASE_DOMAIN') ? BASE_DOMAIN : 'mimargen.cl';

// Load platform settings (OtterErp contract: requires rootDataDir)
$rootDataDir = dirname(__DIR__) . '/data';
$settings    = PlatformSettings::load($rootDataDir);

// Extract settings with fallbacks
$contactEmail    = $settings['contact_email']    ?? ('contacto@' . $baseDomain);
$contactPhone    = $settings['contact_phone']    ?? '+56 9 0000 0000';
$contactWhatsApp = $settings['contact_whatsapp'] ?? '56900000000';
$contactCity     = $settings['contact_city']     ?? 'Santiago, Chile';
$heroLead        = $settings['hero_lead']        ?? 'Crea recetas con tus ingredientes, calcula el costo real de producción —incluyendo merma— y conoce tu margen de ganancia real. Todo en un solo lugar, sin hojas de cálculo que no te cierran.';
$heroTitle       = $settings['hero_title']       ?? 'Conoce cuánto *ganas realmente* con cada producto';

// El título del hero admite *palabra* como marcador: ese tramo se renderiza con
// el acento de marca + subrayado animado. Se escapa primero y luego se inyecta
// el span, de modo que el texto del admin nunca produce HTML arbitrario.
$heroTitleHtml = preg_replace_callback(
    '/\*([^*]+)\*/',
    static fn (array $m): string => '<span class="text-brand-600 hl-underline">' . $m[1] . '</span>',
    htmlspecialchars($heroTitle, ENT_QUOTES, 'UTF-8')
);

$socialLinkedin  = $settings['social_linkedin']  ?? '';
$socialInstagram = $settings['social_instagram'] ?? '';

// Logo (separate methods, not in load())
$brandLogo = PlatformSettings::brandLogoDataUrl($rootDataDir);

// ── Handle POST form submission (honeypot + lead capture) ──────
$formError   = '';
$formSuccess = false;
$formValues  = ['nombre' => '', 'empresa' => '', 'email' => '', 'telefono' => '', 'mensaje' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'lead') {

    // Honeypot — if bot fills this, silently discard
    if (!empty($_POST['website'] ?? '')) {
        $formSuccess = true;
    } else {
        $formValues['nombre']   = trim((string)($_POST['nombre']   ?? ''));
        $formValues['empresa']  = trim((string)($_POST['empresa']  ?? ''));
        $formValues['email']    = trim((string)($_POST['email']    ?? ''));
        $formValues['telefono'] = trim((string)($_POST['telefono'] ?? ''));
        $formValues['mensaje']  = trim((string)($_POST['mensaje']  ?? ''));

        if (mb_strlen($formValues['nombre']) < 2) {
            $formError = 'Cuéntanos tu nombre.';
        } elseif (!filter_var($formValues['email'], FILTER_VALIDATE_EMAIL)) {
            $formError = 'Necesitamos un correo válido para escribirte.';
        } elseif (mb_strlen($formValues['empresa']) < 2) {
            $formError = 'Indica el nombre de tu empresa.';
        } else {
            try {
                $platformDir = dirname(__DIR__) . '/data/_platform';
                if (!is_dir($platformDir)) {
                    @mkdir($platformDir, 0750, true);
                }
                $enc     = new Encryption(APP_SECRET);
                $storage = new JsonStorage($platformDir, $enc);
                $storage->insert('leads', [
                    'nombre'     => $formValues['nombre'],
                    'empresa'    => $formValues['empresa'],
                    'email'      => $formValues['email'],
                    'telefono'   => $formValues['telefono'],
                    'mensaje'    => $formValues['mensaje'],
                    'ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
                    'user_agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                    'referer'    => substr((string)($_SERVER['HTTP_REFERER']    ?? ''), 0, 255),
                    'created_at' => date('c'),
                ]);
                $formSuccess = true;
                $formValues  = ['nombre' => '', 'empresa' => '', 'email' => '', 'telefono' => '', 'mensaje' => ''];
            } catch (Throwable $e) {
                $formError = 'No pudimos guardar tu solicitud. Escríbenos directo a ' . htmlspecialchars($contactEmail) . '.';
            }
        }
    }

    // El modal "Solicita tu prueba" envía por AJAX; respondemos JSON y cortamos
    // el render del HTML. El form del hero sigue funcionando sin JS (full POST).
    if (strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            $formSuccess
                ? ['ok' => true]
                : ['ok' => false, 'error' => $formError !== '' ? $formError : 'No pudimos procesar tu solicitud.'],
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }
}

// ── Canonical / OG ──────────────────────────────────────────────
$scheme    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'] ?? $baseDomain;
$canonical = $scheme . '://' . $host . '/';
$ogImage   = $scheme . '://' . $host . '/assets/og-image.png';

// Page meta (hardcoded — not in PlatformSettings whitelist)
$metaTitle       = $appName . ' · Calcula el costo y margen real de tus productos';
$metaDescription = 'Calcula tu margen de ganancia de verdad: costeo por insumos, merma, mano de obra y precio de venta. Inventario, ventas y facturación electrónica para quienes fabrican o transforman insumos en Chile.';

// JSON-LD Schema
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'SoftwareApplication',
    'name' => $appName,
    'description' => 'Software de gestión para pequeños fabricantes y productores: costeo de productos por insumos, inventario, ventas y facturación electrónica.',
    'url' => $canonical,
    'applicationCategory' => 'BusinessApplication',
    'operatingSystem' => 'Web',
    'offers' => [
        '@type' => 'Offer',
        'price' => '29990',
        'priceCurrency' => 'CLP',
        'description' => 'Desde $29.990 CLP/mes',
    ],
    'aggregateRating' => [
        '@type' => 'AggregateRating',
        'ratingValue' => '4.8',
        'ratingCount' => '127',
    ],
];
$jsonLdString = json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
<!doctype html>
<html lang="es-CL">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($metaTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="title" content="<?= htmlspecialchars($metaTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="keywords" content="calcular costo de producción, margen de ganancia, costeo de productos, software inventario, ERP pymes Chile, control de stock, facturación electrónica, insumos, manufactura">
    <meta name="author" content="<?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#059669">
    <link rel="canonical" href="<?= htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:title" content="<?= htmlspecialchars($metaTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image" content="<?= htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:locale" content="es_CL">
    <meta property="og:site_name" content="<?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?>">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?= htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:title" content="<?= htmlspecialchars($metaTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') ?>">

    <!-- JSON-LD Schema -->
    <script type="application/ld+json"><?= $jsonLdString ?></script>

    <link rel="stylesheet" href="/assets/landing.css">
    <link rel="icon" type="image/x-icon" href="/assets/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
    <link rel="apple-touch-icon" href="/assets/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* ═══════════════════════════════════════════════════════
           PREMIUM CSS ARCHITECTURE — Landing
           ═══════════════════════════════════════════════════════ */

        :root {
            --font-sans: "Geist", system-ui, -apple-system, sans-serif;
            --ease-premium: cubic-bezier(0.32, 0.72, 0, 1);
            --ease-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        html { scroll-behavior: smooth; }
        h1, h2, h3 { text-wrap: balance; }
        p { text-wrap: pretty; }

        /* ── Typography Hierarchy ─────────────────────────── */
        h1 { font-size: clamp(2.5rem, 5vw + 1rem, 4.5rem); font-weight: 900; letter-spacing: -0.03em; line-height: 1.05; }
        h2 { font-size: clamp(2rem, 4vw + 0.5rem, 3rem) !important; font-weight: 800 !important; letter-spacing: -0.02em !important; line-height: 1.1; }
        h3 { font-size: 1.125rem; font-weight: 600; }

        /* ── Section Spacing ─────────────────────────────── */
        .section-spacing { margin-bottom: 0; }

        /* ── Better button padding ───────────────────────── */
        .btn-secondary-fixed { padding: 0.875rem 2rem !important; min-width: 200px; }

        /* ── Header solid on scroll ──────────────────────── */
        .header-solid { background: white !important; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border-bottom-color: rgba(203, 213, 225, 0.4) !important; }

        /* ── Double-Bezel Card Architecture (Stitch-subtle) ── */
        .bezel-outer {
            background: rgba(248, 250, 252, 0.6);
            border: 1px solid rgba(148, 163, 184, 0.35);
            border-radius: 1.75rem;
            padding: 0.5rem;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05), 0 1px 3px rgba(15, 23, 42, 0.04);
        }
        .bezel-inner {
            background: white;
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 1.25rem;
            box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.9), 0 1px 3px rgba(15, 23, 42, 0.05);
            padding: 2rem;
        }

        /* ── Scroll Reveal with Blur ─────────────────────── */
        .reveal {
            opacity: 0;
            transform: translateY(16px);
            filter: blur(4px);
            transition: opacity 700ms var(--ease-premium),
                        transform 700ms var(--ease-premium),
                        filter 700ms var(--ease-premium);
        }
        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
            filter: blur(0);
        }
        .reveal-delay-1 { transition-delay: 0.1s; }
        .reveal-delay-2 { transition-delay: 0.2s; }
        .reveal-delay-3 { transition-delay: 0.3s; }

        @media (prefers-reduced-motion: reduce) {
            .reveal { opacity: 1; transform: none; filter: none; transition: none; }
            .bezel-card:hover { transform: none; }
            .btn-premium:active { transform: none; }
            .hl-underline::after { animation: none; transform: scaleX(1); }
            html { scroll-behavior: auto; }
        }

        /* ── Button Physics ──────────────────────────────── */
        .btn-premium {
            transition: all 350ms var(--ease-premium);
            position: relative;
        }
        .btn-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(16, 185, 129, 0.15), 0 4px 8px rgba(16, 185, 129, 0.08);
        }
        .btn-premium:active {
            transform: translateY(0) scale(0.98);
        }
        .btn-premium .btn-icon {
            transition: transform 350ms var(--ease-premium);
        }
        .btn-premium:hover .btn-icon {
            transform: translateX(4px) translateY(-1px);
        }
        
        /* Secondary button variant */
        .btn-secondary {
            transition: all 350ms var(--ease-premium);
        }
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
            border-color: rgba(203, 213, 225, 0.8);
        }
        .btn-secondary:active {
            transform: translateY(0) scale(0.98);
        }

        /* ── Card Hover ──────────────────────────────────── */
        .bezel-card {
            transition: transform 350ms var(--ease-premium), box-shadow 350ms var(--ease-premium);
        }
        .bezel-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(16, 185, 129, 0.08), 0 4px 8px rgba(16, 185, 129, 0.04);
        }

        /* ── Brand Shadows ───────────────────────────────── */
        .shadow-brand-sm { box-shadow: 0 1px 3px rgba(16,185,129,0.06), 0 1px 2px rgba(16,185,129,0.04); }
        .shadow-brand-md { box-shadow: 0 4px 12px rgba(16,185,129,0.08), 0 2px 4px rgba(16,185,129,0.04); }
        .shadow-brand-lg { box-shadow: 0 12px 32px rgba(16,185,129,0.1), 0 4px 8px rgba(16,185,129,0.04); }
        .shadow-brand-xl { box-shadow: 0 20px 48px rgba(16,185,129,0.12), 0 8px 16px rgba(16,185,129,0.06); }
        .shadow-refined-sm { box-shadow: 0 1px 3px rgba(6,95,70,0.04), 0 1px 2px rgba(6,95,70,0.02); }
        .shadow-refined-md { box-shadow: 0 4px 12px rgba(6,95,70,0.06), 0 2px 4px rgba(6,95,70,0.03); }
        .shadow-refined-lg { box-shadow: 0 12px 32px rgba(6,95,70,0.08), 0 4px 8px rgba(6,95,70,0.03); }

        /* ── Section Backgrounds ─────────────────────────── */
        .section-hero-gradient { background: radial-gradient(ellipse 80% 60% at 30% 20%, rgba(16,185,129,0.06) 0%, rgba(255,255,255,1) 70%); }
        .section-brand-tint { background-color: rgba(236, 253, 245, 0.25); }
        .section-slate-tint { background-color: rgba(248, 250, 252, 0.5); }
        .section-dark-cta { background: linear-gradient(160deg, #0f172a 0%, #1a2636 40%, #0f172a 100%); }

        /* ── Pricing Popular (Z-Axis Cascade) ────────────── */
        .pricing-popular-wrapper {
            transform: scale(1.08);
            z-index: 10;
        }
        @media (max-width: 767px) {
            .pricing-popular-wrapper { transform: scale(1); }
        }
        .pricing-popular {
            position: relative;
        }
        .pricing-popular::before {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: 1.5rem;
            background: linear-gradient(160deg, #10b981, #059669, #065f46);
            z-index: -1;
        }

        /* ── Step Connector ──────────────────────────────── */
        .step-connector {
            position: absolute;
            top: 2rem;
            left: calc(50% + 2.5rem);
            width: calc(100% - 5rem);
            height: 2px;
            background: repeating-linear-gradient(90deg, #10b981 0, #10b981 6px, transparent 6px, transparent 12px);
            opacity: 0.3;
        }

        /* ── Testimonial Avatar ──────────────────────────── */
        .avatar-initials {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.8rem;
            line-height: 1;
            color: white;
            flex-shrink: 0;
        }

        /* ── Quote Decoration ────────────────────────────── */
        .quote-icon::before {
            content: '\201C';
            position: absolute;
            top: -0.25rem;
            left: 0;
            font-size: 3rem;
            line-height: 1;
            color: rgba(16, 185, 129, 0.1);
            font-family: Georgia, serif;
        }

        /* ── FAQ Accordion ───────────────────────────────── */
        details > summary::-webkit-details-marker { display: none; }
        details[open] summary ~ * {
            animation: faqSlideIn 350ms var(--ease-premium);
        }
        @keyframes faqSlideIn {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        details[open].faq-open {
            border-color: rgba(16, 185, 129, 0.3);
        }

        /* ── Calculator Inputs ───────────────────────────── */
        #calculator-widget input[type="number"] { font-variant-numeric: tabular-nums; }

        /* ── Star Rating ─────────────────────────────────── */
        .star-rating { font-size: 1rem; letter-spacing: 2px; }

        /* ── Icon Circle ─────────────────────────────────── */
        .icon-circle {
            width: 3rem;
            height: 3rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ═══════════════════════════════════════════════════════
           DRAMATIC VISUAL IMPROVEMENTS
           ═══════════════════════════════════════════════════════ */

        /* ── Bigger, Bolder Buttons ─────────────────────── */
        .btn-primary-large {
            font-size: 1.125rem;
            padding: 1rem 2.25rem;
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.25), 0 4px 10px rgba(16, 185, 129, 0.15);
            transition: all 400ms var(--ease-premium);
        }
        .btn-primary-large:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(16, 185, 129, 0.35), 0 8px 20px rgba(16, 185, 129, 0.2);
        }
        .btn-primary-large:active {
            transform: translateY(-1px) scale(0.98);
        }

        /* ── Stronger Double-Bezel (now subtle Stitch) ──── */
        .bezel-outer-strong {
            background: rgba(248, 250, 252, 0.6);
            border: 1px solid rgba(148, 163, 184, 0.35);
            border-radius: 1.75rem;
            padding: 0.5rem;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05), 0 1px 3px rgba(15, 23, 42, 0.04);
        }
        .bezel-inner-strong {
            background: white;
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 1.25rem;
            box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.9), 0 1px 3px rgba(15, 23, 42, 0.05);
            padding: 2rem;
        }

        /* ── Pricing Popular Emphasis ───────────────────── */
        .pricing-popular-emphasis {
            transform: scale(1.08);
            box-shadow: 0 25px 50px rgba(16, 185, 129, 0.15), 0 0 0 1px rgba(16, 185, 129, 0.2);
        }
        @media (max-width: 767px) {
            .pricing-popular-emphasis { transform: scale(1); }
        }

        /* ── Section Heading Hierarchy ──────────────────── */
        .heading-major {
            font-size: clamp(2.25rem, 4.5vw + 0.75rem, 3.75rem);
        }
        .heading-minor {
            font-size: clamp(1.75rem, 3.5vw + 0.5rem, 2.75rem);
        }

        /* ── Bento Grid for Features (removed - using simple grid) ── */

        /* ── Section Dividers ───────────────────────────── */
        .section-divider-fade {
            position: relative;
        }
        .section-divider-fade::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 80px;
            background: linear-gradient(to bottom, transparent, rgba(255,255,255,0.8));
            pointer-events: none;
        }
        .section-divider-strong {
            position: relative;
        }
        .section-divider-strong::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 120px;
            height: 4px;
            background: linear-gradient(90deg, transparent, #10b981, transparent);
            border-radius: 2px;
        }

        /* ── Nav Button Enhancement ─────────────────────── */
        .btn-nav {
            padding: 0.625rem 1.5rem;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }
        .btn-nav:hover {
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }

        /* ═══════════════════════════════════════════════════════
           STITCH-INSPIRED MINIMAL ADDITIONS
           ═══════════════════════════════════════════════════════ */

        /* ── More minimal icon style ─────────────────────── */
        .icon-minimal {
            width: 1.25rem;
            height: 1.25rem;
            color: #059669;
        }

        /* ── Horizontal icon row for industries ──────────── */
        .industry-icon-row {
            display: flex;
            justify-content: center;
            gap: 3rem;
            flex-wrap: wrap;
        }
        .industry-icon-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
        }
        .industry-icon-item svg {
            width: 2rem;
            height: 2rem;
            color: #64748b;
        }
        .industry-icon-item span {
            font-size: 0.875rem;
            color: #475569;
            font-weight: 500;
        }

        /* ── Simpler step numbers (Stitch circles) ───────── */
        .step-number-small {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 9999px;
            background: #059669;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            margin: 0 auto 1.5rem;
        }

        /* ── Calculator simple layout ────────────────────── */
        .calc-simple-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }
        @media (max-width: 767px) {
            .calc-simple-row {
                grid-template-columns: 1fr;
            }
        }

        /* ── Result highlight box ────────────────────────── */
        .result-highlight {
            background: rgba(236, 253, 245, 0.8);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 0.75rem;
            padding: 1rem 1.5rem;
            text-align: center;
        }
        .result-highlight .price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #059669;
        }

        /* ── Minimal pain point cards ────────────────────── */
        .pain-card-minimal .bezel-inner {
            padding: 2rem 1.5rem;
        }
        .pain-card-minimal .icon-minimal-wrap {
            margin-bottom: 1rem;
        }

        /* ═══════════════════════════════════════════════════════
           AESTHETIC REFINEMENTS — depth, accents, micro-interactions
           ═══════════════════════════════════════════════════════ */

        /* ── Hero: animated accent underline on highlighted word ── */
        .hl-underline { position: relative; white-space: nowrap; }
        .hl-underline::after {
            content: '';
            position: absolute;
            left: 0; right: 0; bottom: -0.06em;
            height: 0.12em;
            border-radius: 999px;
            background: linear-gradient(90deg, #34d399, #10b981 55%, #059669);
            transform: scaleX(0);
            transform-origin: left center;
            animation: hlGrow 850ms var(--ease-premium) 300ms forwards;
        }
        @keyframes hlGrow { to { transform: scaleX(1); } }

        /* ── Hero: faint blueprint grid behind content ── */
        .hero-grid {
            background-image:
                linear-gradient(rgba(15,23,42,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(15,23,42,0.03) 1px, transparent 1px);
            background-size: 44px 44px;
            -webkit-mask-image: radial-gradient(ellipse 75% 65% at 35% 12%, #000, transparent 72%);
            mask-image: radial-gradient(ellipse 75% 65% at 35% 12%, #000, transparent 72%);
        }

        /* ── Feature icons: soft brand chip + hover pop ── */
        #features .bezel-card .icon-minimal {
            width: 2.75rem; height: 2.75rem; padding: 0.65rem;
            box-sizing: border-box;
            border-radius: 0.85rem;
            background: linear-gradient(135deg, rgba(16,185,129,0.12), rgba(16,185,129,0.05));
            border: 1px solid rgba(16,185,129,0.16);
            color: #059669;
            transition: background 350ms var(--ease-premium),
                        color 350ms var(--ease-premium),
                        transform 350ms var(--ease-premium),
                        border-color 350ms var(--ease-premium);
        }
        #features .bezel-card:hover .icon-minimal {
            background: linear-gradient(135deg, #10b981, #059669);
            border-color: transparent;
            color: #fff;
            transform: translateY(-2px) rotate(-3deg);
        }

        /* ── Same brand chip for pain-point & step icons ── */
        #producto .pain-card-minimal .icon-minimal,
        #como-funciona .bezel-card .icon-minimal {
            display: block;
            margin-left: auto; margin-right: auto;
            width: 3rem; height: 3rem; padding: 0.7rem;
            box-sizing: border-box;
            border-radius: 0.9rem;
            background: linear-gradient(135deg, rgba(16,185,129,0.12), rgba(16,185,129,0.05));
            border: 1px solid rgba(16,185,129,0.16);
            color: #059669;
            transition: background 350ms var(--ease-premium),
                        color 350ms var(--ease-premium),
                        transform 350ms var(--ease-premium),
                        border-color 350ms var(--ease-premium);
        }
        #producto .pain-card-minimal:hover .icon-minimal,
        #como-funciona .bezel-card:hover .icon-minimal {
            background: linear-gradient(135deg, #10b981, #059669);
            border-color: transparent;
            color: #fff;
            transform: translateY(-2px) rotate(-3deg);
        }

        /* ── Stronger card lift on hover ── */
        .bezel-card:hover {
            box-shadow: 0 18px 44px rgba(16,185,129,0.16), 0 6px 14px rgba(15,23,42,0.06);
            border-color: rgba(16,185,129,0.3);
        }

        /* ── Comparison table: highlight the product column ── */
        .comparison-table td:nth-child(2),
        .comparison-table th:nth-child(2) {
            background: rgba(16,185,129,0.08);
            box-shadow: inset 1px 0 0 rgba(16,185,129,0.22), inset -1px 0 0 rgba(16,185,129,0.22);
        }
        .comparison-table thead th:nth-child(2) {
            background: rgba(16,185,129,0.18);
            box-shadow: inset 1px 0 0 rgba(16,185,129,0.28), inset -1px 0 0 rgba(16,185,129,0.28);
        }
        .comparison-table td .inline-flex { gap: 0.375rem; }
        .comparison-table tbody tr:last-child td:nth-child(2) {
            box-shadow: inset 1px 0 0 rgba(16,185,129,0.22), inset -1px 0 0 rgba(16,185,129,0.22), inset 0 -2px 0 rgba(16,185,129,0.28);
            border-bottom-left-radius: 0.5rem;
            border-bottom-right-radius: 0.5rem;
        }

        /* ── Industry icons: lift + colorize on hover ── */
        .industry-icon-item { transition: transform 300ms var(--ease-premium); }
        .industry-icon-item svg,
        .industry-icon-item span { transition: color 300ms var(--ease-premium); }
        .industry-icon-item:hover { transform: translateY(-4px); }
        .industry-icon-item:hover svg,
        .industry-icon-item:hover span { color: #059669; }

        /* ── Dark CTA: green top-glow + subtle dot grid for depth ── */
        .section-dark-cta {
            position: relative;
            background:
                radial-gradient(ellipse 55% 45% at 50% 0%, rgba(16,185,129,0.14), transparent 70%),
                linear-gradient(160deg, #0f172a 0%, #1a2636 42%, #0f172a 100%);
        }
        .section-dark-cta::before {
            content: '';
            position: absolute; inset: 0;
            background-image: radial-gradient(rgba(255,255,255,0.05) 1px, transparent 1px);
            background-size: 24px 24px;
            -webkit-mask-image: radial-gradient(ellipse 70% 65% at 50% 35%, #000, transparent 78%);
            mask-image: radial-gradient(ellipse 70% 65% at 50% 35%, #000, transparent 78%);
            pointer-events: none;
        }
        .section-dark-cta > * { position: relative; z-index: 1; }

        /* ── Modal "Ya soy cliente" (slug → subdominio) ──────────── */
        .client-modal {
            position: fixed; inset: 0; z-index: 100;
            background: rgba(15,23,42,.55); backdrop-filter: blur(4px);
            display: none; align-items: center; justify-content: center; padding: 18px;
        }
        .client-modal[aria-hidden="false"] { display: flex; }
        .client-modal-box {
            background: #fff; border-radius: 1.25rem; padding: 26px; width: 100%; max-width: 420px;
            box-shadow: 0 30px 80px -20px rgba(15,23,42,.4);
        }
        .client-modal-box h3 { margin: 0 0 6px; font-size: 1.15rem; font-weight: 700; color: #0f172a; }
        .client-modal-box p { margin: 0 0 18px; color: #64748b; font-size: .88rem; line-height: 1.5; }
        .client-modal-row { display: flex; align-items: stretch; }
        .client-modal-row input {
            flex: 1; min-width: 0; padding: 11px 14px; border: 1.5px solid #e2e8f0;
            border-radius: 10px 0 0 10px; font-family: inherit; font-size: .95rem; outline: none;
        }
        .client-modal-row input:focus { border-color: #10b981; }
        .client-modal-suffix {
            display: inline-flex; align-items: center; padding: 0 14px;
            background: #f1f5f9; color: #64748b; font-size: .88rem;
            border: 1.5px solid #e2e8f0; border-left: none; border-radius: 0 10px 10px 0; font-weight: 500;
        }
        .client-error { margin-top: 8px; color: #be123c; font-size: .8rem; min-height: 18px; }
        .client-modal-actions { display: flex; gap: 10px; margin-top: 18px; }
        .client-modal-btn {
            flex: 1; padding: 11px 16px; border-radius: 10px; font-family: inherit;
            font-size: .9rem; font-weight: 600; cursor: pointer; border: 1.5px solid transparent;
        }
        .client-modal-btn-ghost { background: #fff; color: #1e293b; border-color: #e2e8f0; }
        .client-modal-btn-ghost:hover { border-color: #cbd5e1; background: #f8fafc; }
        .client-modal-btn-primary { background: #059669; color: #fff; }
        .client-modal-btn-primary:hover { background: #047857; }
        .client-modal-sr {
            position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
            overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0;
        }

        /* ── Banner de cookies (consentimiento opt-in, ley Chile / RGPD) ── */
        .cookie-banner {
            position: fixed; left: 0; right: 0; bottom: 0; z-index: 90;
            display: none; padding: 14px;
        }
        .cookie-banner[data-show="1"] { display: block; }
        .cookie-banner-inner {
            max-width: 72rem; margin: 0 auto; background: #fff;
            border: 1px solid rgba(148,163,184,.35); border-radius: 1rem;
            box-shadow: 0 14px 44px rgba(15,23,42,.16);
            padding: 18px 20px;
            display: flex; flex-wrap: wrap; align-items: center; gap: 14px 22px;
        }
        .cookie-banner-text { flex: 1 1 320px; font-size: .85rem; color: #475569; line-height: 1.55; }
        .cookie-banner-actions { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .cookie-btn {
            padding: 10px 20px; border-radius: 9999px; font-size: .85rem; font-weight: 600;
            font-family: inherit; cursor: pointer; border: 1.5px solid transparent; white-space: nowrap;
        }
        .cookie-btn-ghost { background: #fff; color: #1e293b; border-color: #cbd5e1; }
        .cookie-btn-ghost:hover { background: #f8fafc; border-color: #94a3b8; }
        .cookie-btn-primary { background: #059669; color: #fff; }
        .cookie-btn-primary:hover { background: #047857; }
        .cookie-btn-link { background: none; border: none; color: #64748b; text-decoration: underline; padding: 10px 6px; }
        .cookie-btn-link:hover { color: #0f172a; }

        /* Categorías + toggle del modal de preferencias */
        .cookie-cat {
            display: flex; align-items: flex-start; justify-content: space-between;
            gap: 16px; padding: 14px 0; border-top: 1px solid #f1f5f9;
        }
        .cookie-cat:first-of-type { border-top: none; }
        .cookie-cat h4 { margin: 0 0 3px; font-size: .92rem; font-weight: 600; color: #0f172a; }
        .cookie-cat p { margin: 0; font-size: .8rem; color: #64748b; line-height: 1.45; }
        .cookie-switch { position: relative; flex-shrink: 0; width: 42px; height: 24px; }
        .cookie-switch input { position: absolute; opacity: 0; width: 0; height: 0; }
        .cookie-switch span {
            position: absolute; inset: 0; border-radius: 9999px; background: #cbd5e1;
            transition: background .2s; cursor: pointer;
        }
        .cookie-switch span::after {
            content: ''; position: absolute; top: 3px; left: 3px; width: 18px; height: 18px;
            border-radius: 9999px; background: #fff; transition: transform .2s;
            box-shadow: 0 1px 2px rgba(15,23,42,.2);
        }
        .cookie-switch input:checked + span { background: #059669; }
        .cookie-switch input:checked + span::after { transform: translateX(18px); }
        .cookie-switch input:disabled + span { background: #6ee7b7; cursor: not-allowed; }
        .cookie-switch input:focus-visible + span { outline: 2px solid #10b981; outline-offset: 2px; }
    </style>
</head>
<body class="font-sans antialiased text-slate-800 bg-white">

<!-- Header -->
<header id="site-header" class="fixed top-0 left-0 right-0 z-50 bg-white/0 border-b border-transparent transition-all duration-300">
    <nav class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <!-- Logo -->
        <a href="#" class="flex items-center gap-2.5 group">
            <img src="<?= $brandLogo ? htmlspecialchars($brandLogo, ENT_QUOTES, 'UTF-8') : '/assets/logo-icon.svg' ?>" alt="<?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?>" class="w-8 h-8 group-hover:scale-105 transition-all duration-350" style="transition-timing-function: var(--ease-premium)">
            <span class="text-lg font-bold text-slate-900 tracking-tight"><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></span>
        </a>

        <!-- Desktop Nav -->
        <div class="hidden md:flex items-center gap-8">
            <a href="#producto" class="text-sm font-medium text-slate-600 hover:text-brand-600 transition-colors duration-350" style="transition-timing-function: var(--ease-premium)">Producto</a>
            <a href="#precios" class="text-sm font-medium text-slate-600 hover:text-brand-600 transition-colors duration-350" style="transition-timing-function: var(--ease-premium)">Precios</a>
            <a href="#calculadora" class="text-sm font-medium text-slate-600 hover:text-brand-600 transition-colors duration-350" style="transition-timing-function: var(--ease-premium)">Calculadora</a>
            <a href="#faq" class="text-sm font-medium text-slate-600 hover:text-brand-600 transition-colors duration-350" style="transition-timing-function: var(--ease-premium)">FAQ</a>
            <a href="#" data-client-open class="text-sm font-medium text-slate-600 hover:text-brand-600 transition-colors duration-350" style="transition-timing-function: var(--ease-premium)">Ingresar</a>
            <a href="#" data-trial-open class="ml-2 btn-premium btn-nav inline-flex items-center rounded-full bg-brand-600 text-white text-sm font-semibold hover:bg-brand-700 shadow-brand-md hover:shadow-brand-lg">Pruébalo gratis</a>
        </div>

        <!-- Mobile Menu Button -->
        <button id="mobile-menu-btn" class="md:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100 transition-colors" aria-label="Abrir menú">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </nav>

    <!-- Mobile Menu -->
    <div id="mobile-menu" class="hidden md:hidden bg-white border-t border-slate-200/60">
        <div class="px-4 py-4 space-y-3">
            <a href="#producto" class="block text-sm font-medium text-slate-600 hover:text-brand-600 transition-colors py-2">Producto</a>
            <a href="#precios" class="block text-sm font-medium text-slate-600 hover:text-brand-600 transition-colors py-2">Precios</a>
            <a href="#calculadora" class="block text-sm font-medium text-slate-600 hover:text-brand-600 transition-colors py-2">Calculadora</a>
            <a href="#faq" class="block text-sm font-medium text-slate-600 hover:text-brand-600 transition-colors py-2">FAQ</a>
            <a href="#" data-client-open class="block text-sm font-medium text-slate-600 hover:text-brand-600 transition-colors py-2">Ingresar</a>
            <a href="#" data-trial-open class="block text-center px-4 py-2.5 rounded-full bg-brand-600 text-white text-sm font-semibold hover:bg-brand-700 transition-colors">Pruébalo gratis</a>
        </div>
    </div>
</header>

<main>
<!-- Hero -->
<section class="relative pt-32 pb-16 sm:pt-40 sm:pb-20 lg:pt-44 lg:pb-24 overflow-hidden section-hero-gradient section-divider-fade">
    <!-- Subtle background decoration -->
    <div class="absolute inset-0 -z-10">
        <div class="absolute top-0 right-0 w-[50rem] h-[50rem] bg-brand-100/20 rounded-full blur-[100px] -translate-y-1/3 translate-x-1/4"></div>
        <div class="absolute bottom-0 left-0 w-[34rem] h-[34rem] bg-brand-200/15 rounded-full blur-[110px] translate-y-1/3 -translate-x-1/4"></div>
        <div class="hero-grid absolute inset-0"></div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            <!-- Text (50%) -->
            <div class="text-center lg:text-left">
                <h1 class="text-slate-900">
                    <?= $heroTitleHtml ?>
                </h1>
                <p class="mt-8 text-lg sm:text-xl text-slate-600 leading-relaxed max-w-xl mx-auto lg:mx-0">
                    <?= htmlspecialchars($heroLead, ENT_QUOTES, 'UTF-8') ?>
                </p>
                <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    <a href="#" data-trial-open class="btn-primary-large btn-premium inline-flex items-center justify-center font-semibold rounded-full text-white group">
                        Pruébalo gratis 14 días
                        <span class="btn-icon ml-2 inline-flex">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                        </span>
                    </a>
                    <a href="#como-funciona" class="btn-secondary btn-secondary-fixed inline-flex items-center justify-center font-medium rounded-full text-base py-3.5 bg-white text-slate-700 border border-slate-200 hover:bg-slate-50 hover:border-slate-300 shadow-refined-sm">
                        <svg class="w-5 h-5 mr-2 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Ver cómo funciona
                    </a>
                </div>
                <p class="mt-6 text-sm text-slate-500">Sin tarjeta de crédito · 14 días gratis · Sin compromiso</p>
            </div>

            <!-- Lead capture form (50%) — Double-Bezel -->
            <div id="contacto">
                <?php if ($formSuccess): ?>
                    <div class="bezel-outer" style="box-shadow: 0 20px 50px rgba(0,0,0,0.06), 0 8px 20px rgba(0,0,0,0.03);">
                        <div class="bezel-inner text-center py-10">
                            <div class="mx-auto w-14 h-14 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center mb-4">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            </div>
                            <p class="text-brand-700 font-semibold text-lg">¡Gracias! Te contactaremos pronto.</p>
                        </div>
                    </div>
                <?php elseif ($formError): ?>
                    <div class="bezel-outer" style="box-shadow: 0 20px 50px rgba(0,0,0,0.06), 0 8px 20px rgba(0,0,0,0.03);">
                        <div class="bezel-inner">
                            <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                                <p class="text-red-600 font-semibold"><?= htmlspecialchars($formError, ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!$formSuccess): ?>
                <div class="bezel-outer" style="box-shadow: 0 20px 50px rgba(0,0,0,0.06), 0 8px 20px rgba(0,0,0,0.03);">
                <form method="POST" action="#contacto" class="bezel-inner">
                    <input type="hidden" name="action" value="lead">
                    <!-- Honeypot — must stay hidden -->
                    <input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;opacity:0;height:0;width:0" aria-hidden="true">

                    <h3 class="text-xl font-semibold text-slate-900 mb-1">Calcula tu rentabilidad hoy</h3>
                    <p class="text-sm text-slate-500 mb-6">14 días sin tarjeta de crédito</p>

                    <div class="space-y-4">
                        <div>
                            <label for="lead-nombre" class="block text-xs font-medium text-slate-600 mb-1.5">Nombre</label>
                            <input type="text" id="lead-nombre" name="nombre" required
                                   value="<?= htmlspecialchars($formValues['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                                   class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-350 bg-slate-50/50 focus:bg-white"
                                   style="transition-timing-function: var(--ease-premium)"
                                   placeholder="Tu nombre">
                        </div>
                        <div>
                            <label for="lead-empresa" class="block text-xs font-medium text-slate-600 mb-1.5">Empresa</label>
                            <input type="text" id="lead-empresa" name="empresa" required
                                   value="<?= htmlspecialchars($formValues['empresa'], ENT_QUOTES, 'UTF-8') ?>"
                                   class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-350 bg-slate-50/50 focus:bg-white"
                                   style="transition-timing-function: var(--ease-premium)"
                                   placeholder="Nombre de tu empresa">
                        </div>
                        <div>
                            <label for="lead-email" class="block text-xs font-medium text-slate-600 mb-1.5">Correo electrónico</label>
                            <input type="email" id="lead-email" name="email" required
                                   value="<?= htmlspecialchars($formValues['email'], ENT_QUOTES, 'UTF-8') ?>"
                                   class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-350 bg-slate-50/50 focus:bg-white"
                                   style="transition-timing-function: var(--ease-premium)"
                                   placeholder="tu@email.com">
                        </div>
                    </div>

                    <button type="submit"
                            class="btn-premium mt-6 w-full inline-flex items-center justify-center font-semibold rounded-full text-lg py-3.5 bg-brand-600 text-white hover:bg-brand-700 shadow-brand-lg hover:shadow-brand-xl">
                        Pruébalo gratis 14 días
                    </button>

                    <p class="mt-3 text-center text-xs text-slate-400">Sin compromiso · Cancela cuando quieras</p>
                </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Pain Points -->
<section id="producto" class="py-16 sm:py-20 bg-white section-spacing">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-12 sm:mb-16 reveal">
            <h2 class="text-slate-900">¿Te suena familiar?</h2>
            <p class="mt-5 text-lg text-slate-600 leading-relaxed">La mayoría de los emprendedores pierden plata sin saberlo. No por falta de esfuerzo, sino por falta de visibilidad.</p>
        </div>
        <div class="grid md:grid-cols-3 gap-8 lg:gap-10">
            <!-- Card 1 — Double-Bezel minimal -->
            <div class="bezel-outer bezel-card reveal pain-card-minimal">
                <div class="bezel-inner text-center">
                    <div class="icon-minimal-wrap">
                        <svg class="icon-minimal mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /><circle cx="17" cy="7" r="2" fill="currentColor" opacity="0.3"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900 mb-2">Pones precios a ojo</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Sumas los insumos, le multiplicas por dos y esperas que alcance. Pero nunca sabes si realmente estás ganando o perdiendo plata.</p>
                </div>
            </div>
            <!-- Card 2 — Double-Bezel minimal -->
            <div class="bezel-outer bezel-card reveal reveal-delay-1 pain-card-minimal">
                <div class="bezel-inner text-center">
                    <div class="icon-minimal-wrap">
                        <svg class="icon-minimal mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 6A2.25 2.25 0 016 3.75h12A2.25 2.25 0 0120.25 6v12A2.25 2.25 0 0118 20.25H6A2.25 2.25 0 013.75 18V6zM3.75 9h16.5M3.75 14.25h16.5M9 9v11.25M15 9v11.25" /></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900 mb-2">Tu Excel ya no da para más</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Tienes diez hojas de cálculo, fórmulas que se rompen solas y cada vez que cambia un precio de insumo, tienes que actualizar todo a mano.</p>
                </div>
            </div>
            <!-- Card 3 — Double-Bezel minimal -->
            <div class="bezel-outer bezel-card reveal reveal-delay-2 pain-card-minimal">
                <div class="bezel-inner text-center">
                    <div class="icon-minimal-wrap">
                        <svg class="icon-minimal mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900 mb-2">No sabes cuánto stock te queda</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Aceptas un pedido grande y recién al otro día te das cuenta de que no tienes suficiente materia prima. El stock lo llevas en la cabeza.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works -->
<section id="como-funciona" class="py-16 sm:py-20 lg:py-24 section-brand-tint section-spacing">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-14 sm:mb-16 reveal">
            <h2 class="text-slate-900">Tres pasos hacia el control total</h2>
            <p class="mt-5 text-lg text-slate-600 leading-relaxed">Sin complicaciones. Sin hojas de cálculo. Sin contabilidad avanzada.</p>
        </div>
        <div class="grid md:grid-cols-3 gap-12 lg:gap-16">
            <!-- Step 1 -->
            <div class="relative text-center reveal">
                <div class="step-number-small">1</div>
                <div class="step-connector hidden md:block"></div>
                <div class="bezel-outer bezel-card">
                    <div class="bezel-inner">
                        <svg class="icon-minimal mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        <h3 class="text-lg font-semibold text-slate-900 mb-2">Define tu producto</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">Agrega los insumos o materiales que usas, con sus cantidades exactas. Puedes usar productos de tu inventario o crear insumos nuevos.</p>
                    </div>
                </div>
            </div>
            <!-- Step 2 -->
            <div class="relative text-center reveal reveal-delay-1">
                <div class="step-number-small">2</div>
                <div class="step-connector hidden md:block"></div>
                <div class="bezel-outer bezel-card">
                    <div class="bezel-inner">
                        <svg class="icon-minimal mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                        <h3 class="text-lg font-semibold text-slate-900 mb-2">Calcula el costo real</h3>
                        <p class="text-slate-600 text-sm leading-relaxed"><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> suma automáticamente el costo de insumos, mano de obra, gastos fijos y la merma que generas en el proceso. Sin fórmulas, sin errores.</p>
                    </div>
                </div>
            </div>
            <!-- Step 3 -->
            <div class="relative text-center reveal reveal-delay-2">
                <div class="step-number-small">3</div>
                <div class="bezel-outer bezel-card">
                    <div class="bezel-inner">
                        <svg class="icon-minimal mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                        <h3 class="text-lg font-semibold text-slate-900 mb-2">Conoce tu margen</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">Pon tu precio de venta y ve al instante cuánto ganas realmente por cada producto. Si el margen no te cierra, ajustas y recalculas en segundos.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features -->
<section id="features" class="py-16 sm:py-20 bg-white section-spacing">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-14 sm:mb-16 reveal">
            <h2 class="text-slate-900">Más que costos. Tu negocio entero.</h2>
            <p class="mt-5 text-lg text-slate-600 leading-relaxed">Todo lo que otros ERPs no tienen en un solo lugar.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Feature 1 -->
            <div class="bezel-outer bezel-card reveal group">
                <div class="bezel-inner">
                    <svg class="icon-minimal mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                    <h3 class="text-base font-semibold text-slate-900 mb-1.5">Costeo por producto</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Calcula el costo real de cada producto incluyendo insumos, mano de obra, merma y gastos fijos.</p>
                </div>
            </div>
            <!-- Feature 2 -->
            <div class="bezel-outer bezel-card reveal reveal-delay-1 group">
                <div class="bezel-inner">
                    <svg class="icon-minimal mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                    <h3 class="text-base font-semibold text-slate-900 mb-1.5">Inventario en tiempo real</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Cada vez que produces o vendes, tu stock se actualiza solo. Alertas cuando algo está por agotarse.</p>
                </div>
            </div>
            <!-- Feature 3 -->
            <div class="bezel-outer bezel-card reveal reveal-delay-2 group">
                <div class="bezel-inner">
                    <svg class="icon-minimal mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    <h3 class="text-base font-semibold text-slate-900 mb-1.5">Gestión de ventas</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Cotizaciones, órdenes de venta y seguimiento de clientes. Todo conectado a tu inventario.</p>
                </div>
            </div>
            <!-- Feature 4 -->
            <div class="bezel-outer bezel-card reveal group">
                <div class="bezel-inner">
                    <svg class="icon-minimal mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" /></svg>
                    <h3 class="text-base font-semibold text-slate-900 mb-1.5">Facturación electrónica</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Emite boletas, facturas y guías de despacho directamente desde <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?>. Integrado con el SII.</p>
                </div>
            </div>
            <!-- Feature 5 -->
            <div class="bezel-outer bezel-card reveal reveal-delay-1 group">
                <div class="bezel-inner">
                    <svg class="icon-minimal mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <h3 class="text-base font-semibold text-slate-900 mb-1.5">Control de caja</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Gestiona tus cuentas bancarias, registra ingresos y egresos, y mira tu flujo de caja en un vistazo.</p>
                </div>
            </div>
            <!-- Feature 6 -->
            <div class="bezel-outer bezel-card reveal reveal-delay-2 group">
                <div class="bezel-inner">
                    <svg class="icon-minimal mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    <h3 class="text-base font-semibold text-slate-900 mb-1.5">Reportes claros</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Rentabilidad por producto, productos más vendidos y evolución de costos. Sin contabilidad avanzada.</p>
                </div>
            </div>
        </div>

        <!-- Comparison Table -->
        <div class="mt-16 max-w-3xl mx-auto reveal">
            <div class="bezel-outer" style="box-shadow: 0 12px 32px rgba(6,95,70,0.08), 0 4px 8px rgba(6,95,70,0.03);">
            <div class="bezel-inner overflow-hidden p-0">
                <div class="overflow-x-auto -mx-4 sm:mx-0">
                <table class="comparison-table w-full text-sm">
                    <thead>
                        <tr class="bg-brand-50">
                            <th class="text-left py-4 px-6 font-semibold text-slate-900" style="width: 40%"></th>
                            <th class="text-center py-4 px-6 font-semibold text-brand-600" style="width: 20%"><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></th>
                            <th class="text-center py-4 px-6 font-semibold text-slate-400" style="width: 20%">Excel</th>
                            <th class="text-center py-4 px-6 font-semibold text-slate-400" style="width: 20%">ERPs tradicionales</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr class="hover:bg-slate-50/50 transition-colors duration-350" style="transition-timing-function: var(--ease-premium)">
                            <td class="py-4 px-6 text-slate-700 font-medium">Costeo por producto</td>
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex items-center gap-1.5 text-brand-600 font-semibold text-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    Incluido
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center text-slate-400">Fórmulas manuales</td>
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex items-center gap-1.5 text-red-400 text-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                    No existe
                                </span>
                            </td>
                        </tr>
                        <tr class="hover:bg-slate-50/50 transition-colors duration-350" style="transition-timing-function: var(--ease-premium)">
                            <td class="py-4 px-6 text-slate-700 font-medium">Facturación electrónica</td>
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex items-center gap-1.5 text-brand-600 font-semibold text-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    Integrada
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center text-slate-400">Por separado</td>
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex items-center gap-1.5 text-amber-500 text-sm" style="color:#f59e0b;">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m0 3h.01M10.06 3.4 1.7 18a2 2 0 0 0 1.73 3h17.14a2 2 0 0 0 1.73-3L13.94 3.4a2 2 0 0 0-3.88 0Z"/></svg>
                                    A veces
                                </span>
                            </td>
                        </tr>
                        <tr class="hover:bg-slate-50/50 transition-colors duration-350" style="transition-timing-function: var(--ease-premium)">
                            <td class="py-4 px-6 text-slate-700 font-medium">Inventario automático</td>
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex items-center gap-1.5 text-brand-600 font-semibold text-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    En tiempo real
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center text-slate-400">Manual</td>
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex items-center gap-1.5 text-amber-500 text-sm" style="color:#f59e0b;">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m0 3h.01M10.06 3.4 1.7 18a2 2 0 0 0 1.73 3h17.14a2 2 0 0 0 1.73-3L13.94 3.4a2 2 0 0 0-3.88 0Z"/></svg>
                                    Complejo
                                </span>
                            </td>
                        </tr>
                        <tr class="hover:bg-slate-50/50 transition-colors duration-350" style="transition-timing-function: var(--ease-premium)">
                            <td class="py-4 px-6 text-slate-700 font-medium">Curva de aprendizaje</td>
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex items-center gap-1.5 text-brand-600 font-semibold text-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    Horas
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center text-slate-400">—</td>
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex items-center gap-1.5 text-red-400 text-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Semanas
                                </span>
                            </td>
                        </tr>
                        <tr class="hover:bg-slate-50/50 transition-colors duration-350" style="transition-timing-function: var(--ease-premium)">
                            <td class="py-4 px-6 text-slate-700 font-medium">Precio accesible</td>
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex items-center gap-1.5 text-brand-600 font-bold text-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    Desde $29.990/mes
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center text-slate-400">"Gratis"</td>
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex items-center gap-1.5 text-red-400 text-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                    $200.000+/mes
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>
            </div>
        </div>
    </div>
</section>

<!-- Who It's For -->
<section id="para-quien" class="py-16 sm:py-20 section-slate-tint section-spacing">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-14 sm:mb-16 reveal">
            <h2 class="text-slate-900">Hecho para quienes transforman insumos en productos</h2>
            <p class="mt-5 text-lg text-slate-600 leading-relaxed">Si combinas materiales o insumos para fabricar un producto terminado, <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> es para ti.</p>
        </div>
        <div class="industry-icon-row reveal">
            <!-- Industry 1: Alimentos -->
            <div class="industry-icon-item">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8.25v-1.5m-3 1.5v-1.5m6 1.5v-1.5M6 10.608c0-1.135.845-2.098 1.976-2.192a48.42 48.42 0 0 1 8.048 0C17.155 8.51 18 9.473 18 10.608v2.513m-12 0a48.45 48.45 0 0 0-1.163.16c-1.07.16-1.837 1.094-1.837 2.175v5.169c0 .621.504 1.125 1.125 1.125h19.5c.621 0 1.125-.504 1.125-1.125v-5.17c0-1.08-.768-2.014-1.837-2.174A48.6 48.6 0 0 0 18 13.12M6 13.12c1.99-.246 4-.371 6-.371s4.01.125 6 .371m-12 0V8.443m12 4.677V8.443m0 0a48.41 48.41 0 0 0-12 0M3 16.5l1.5.75a3.354 3.354 0 0 0 3 0 3.354 3.354 0 0 1 3 0 3.354 3.354 0 0 0 3 0 3.354 3.354 0 0 1 3 0 3.354 3.354 0 0 0 3 0L21 16.5" /></svg>
                <span>Alimentos</span>
            </div>
            <!-- Industry 2: Bebidas -->
            <div class="industry-icon-item">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 0-6.23-.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" /></svg>
                <span>Bebidas</span>
            </div>
            <!-- Industry 3: Cosmética -->
            <div class="industry-icon-item">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z" /></svg>
                <span>Cosmética</span>
            </div>
            <!-- Industry 4: Manufactura -->
            <div class="industry-icon-item">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437" /></svg>
                <span>Manufactura</span>
            </div>
            <!-- Industry 5: Artesanía -->
            <div class="industry-icon-item">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42" /></svg>
                <span>Artesanía</span>
            </div>
            <!-- Industry 6: Industria -->
            <div class="industry-icon-item">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21a.75.75 0 0 1 .75.75V21" /></svg>
                <span>Industria</span>
            </div>
        </div>
    </div>
</section>

<!-- Calculator -->
<section id="calculadora" class="py-16 sm:py-20 lg:py-24 bg-white section-spacing">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-14 sm:mb-16 reveal">
            <h2 class="text-slate-900">Simula el costo de un producto</h2>
            <p class="mt-4 text-lg text-slate-600 leading-relaxed">No necesitas registrarte ni dar tu email. Ingresa tus insumos, cantidades y precios — y la calculadora te devuelve el costo total.</p>
        </div>
        <div class="max-w-2xl mx-auto reveal">
            <div class="bezel-outer" style="box-shadow: 0 12px 32px rgba(6,95,70,0.08), 0 4px 8px rgba(6,95,70,0.03);">
            <div id="calculator-widget" class="bezel-inner">
                <h3 class="text-xl font-semibold text-slate-900 mb-1.5">Calcula el costo de tu producto</h3>
                <p class="text-sm text-slate-500 mb-6">Ingresa tus insumos y obtén el costo real al instante.</p>

                <!-- Rate Limit Notice -->
                <div id="calc-rate-limit-notice" class="hidden mb-6 p-4 rounded-xl bg-amber-50 border border-amber-200">
                    <p class="text-sm text-amber-800 font-medium">Has alcanzado el límite de cálculos gratuitos por hoy.</p>
                    <p class="text-xs text-amber-600 mt-1">
                        <a href="#" data-trial-open class="underline font-semibold hover:text-amber-900">Prueba <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> gratis</a> para cálculos ilimitados.
                    </p>
                </div>

                <!-- Usage Counter -->
                <div id="calc-usage-counter" class="text-xs text-slate-400 mb-4">
                    Cálculos restantes hoy: <span id="calc-remaining-count" class="font-bold text-slate-600">3</span>
                </div>

                <!-- Ingredients -->
                <div id="calc-ingredients" class="space-y-3 mb-6">
                    <div class="ingredient-row calc-simple-row">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Insumo</label>
                            <input type="text" placeholder="Ej: Insumo A" class="ing-name w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Cantidad</label>
                            <input type="number" placeholder="500" min="0" step="any" class="ing-qty w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Precio unit.</label>
                            <input type="number" placeholder="1.200" min="0" step="any" class="ing-price w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition" />
                        </div>
                        <div class="flex items-end">
                            <button type="button" class="remove-ing hidden w-8 h-8 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 transition flex items-center justify-center" aria-label="Eliminar insumo">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <button type="button" id="calc-add-ingredient" class="text-sm text-brand-600 hover:text-brand-700 font-medium mb-6 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Añadir insumo
                </button>

                <!-- Additional Inputs -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Mano de obra ($/hora)</label>
                        <input type="number" id="calc-labor" placeholder="4.200" min="0" class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Horas por lote</label>
                        <input type="number" id="calc-hours" placeholder="1" min="0" step="0.5" class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Merma (%)</label>
                        <input type="number" id="calc-waste" placeholder="8" min="0" max="100" class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Unidades producidas</label>
                        <input type="number" id="calc-units" placeholder="10" min="1" class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition" />
                    </div>
                </div>

                <!-- Calculate Button -->
                <button type="button" id="calc-calculate" class="btn-premium w-full py-3.5 rounded-full bg-brand-600 text-white font-semibold hover:bg-brand-700 shadow-brand-lg hover:shadow-brand-xl">
                    Calcular costo
                </button>

                <!-- Results -->
                <div id="calc-results" class="hidden mt-6 result-highlight">
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-slate-500">Materiales</span>
                        <span id="res-materials" class="font-medium text-slate-700">$0</span>
                    </div>
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-slate-500">Mano de obra</span>
                        <span id="res-labor" class="font-medium text-slate-700">$0</span>
                    </div>
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-slate-500">Merma</span>
                        <span id="res-waste" class="font-medium text-slate-700">$0</span>
                    </div>
                    <div class="border-t border-green-200/50 pt-3 mt-3">
                        <div class="text-center">
                            <span class="text-sm text-slate-500">Costo estimado</span>
                            <div id="res-total" class="price">$0</div>
                        </div>
                    </div>
                    <div class="flex justify-between text-sm mt-2">
                        <span class="text-slate-500">Costo por unidad</span>
                        <span id="res-unit" class="font-medium text-brand-600">$0</span>
                    </div>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-3 text-center mt-3">
                        <span class="text-sm font-bold text-green-700" id="res-margin">Margen sugerido (50%): $0 por unidad</span>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </div>
</section>
<section id="testimonios" class="py-16 sm:py-20 section-brand-tint section-spacing">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-14 sm:mb-16 reveal">
            <h2 class="text-slate-900">Lo que dicen quienes ya <span class="text-brand-600">lo usan</span></h2>
        </div>
        <div class="grid md:grid-cols-3 gap-8 lg:gap-10">
            <!-- Testimonial 1 -->
            <div class="bezel-outer bezel-card reveal flex flex-col">
                <div class="bezel-inner flex flex-col flex-1">
                    <div class="flex-1">
                        <div class="flex items-center gap-1 mb-4 star-rating">
                            <span class="text-amber-400">★</span>
                            <span class="text-amber-400">★</span>
                            <span class="text-amber-400">★</span>
                            <span class="text-amber-400">★</span>
                            <span class="text-amber-400">★</span>
                        </div>
                        <blockquote class="relative text-slate-700 text-sm leading-relaxed mb-6 pl-0 quote-icon">
                            "Antes pensaba que ganaba $500 por unidad. Con <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> descubrí que, con la merma y el tiempo de producción, ganaba $120. Ajusté precios y ahora mi margen real es del 34%. Ojalá lo hubiera usado antes."
                        </blockquote>
                    </div>
                    <div class="border-t border-slate-100 pt-4 flex items-center gap-3">
                        <div class="avatar-initials bg-brand-600">CM</div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-slate-900 text-sm">Carolina Muñoz</p>
                            <p class="text-slate-500 text-xs">Taller de manufactura, Santiago</p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 whitespace-nowrap">Margen: 34%</span>
                    </div>
                </div>
            </div>
            <!-- Testimonial 2 -->
            <div class="bezel-outer bezel-card reveal reveal-delay-1 flex flex-col">
                <div class="bezel-inner flex flex-col flex-1">
                    <div class="flex-1">
                        <div class="flex items-center gap-1 mb-4 star-rating">
                            <span class="text-amber-400">★</span>
                            <span class="text-amber-400">★</span>
                            <span class="text-amber-400">★</span>
                            <span class="text-amber-400">★</span>
                            <span class="text-amber-400">★</span>
                        </div>
                        <blockquote class="relative text-slate-700 text-sm leading-relaxed mb-6 pl-0 quote-icon">
                            "Perdía plata en tres de mis productos y no tenía idea. <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> me mostró exactamente cuáles y por qué. En dos semanas ya había corregido los precios. Hoy facturo un 22% más con el mismo volumen."
                        </blockquote>
                    </div>
                    <div class="border-t border-slate-100 pt-4 flex items-center gap-3">
                        <div class="avatar-initials bg-amber-600">DA</div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-slate-900 text-sm">Diego Aravena</p>
                            <p class="text-slate-500 text-xs">Productora de cosméticos, Valparaíso</p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 whitespace-nowrap">+22% facturación</span>
                    </div>
                </div>
            </div>
            <!-- Testimonial 3 -->
            <div class="bezel-outer bezel-card reveal reveal-delay-2 flex flex-col">
                <div class="bezel-inner flex flex-col flex-1">
                    <div class="flex-1">
                        <div class="flex items-center gap-1 mb-4 star-rating">
                            <span class="text-amber-400">★</span>
                            <span class="text-amber-400">★</span>
                            <span class="text-amber-400">★</span>
                            <span class="text-amber-400">★</span>
                            <span class="text-amber-400">★</span>
                        </div>
                        <blockquote class="relative text-slate-700 text-sm leading-relaxed mb-6 pl-0 quote-icon">
                            "Tenía todo en Excel y era un desastre. Ahora creo el producto, pongo el precio y veo mi margen al instante. Además, las facturas electrónicas me salen directo desde ahí. Me ahorré contratar a alguien más para eso."
                        </blockquote>
                    </div>
                    <div class="border-t border-slate-100 pt-4 flex items-center gap-3">
                        <div class="avatar-initials bg-purple-600">FL</div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-slate-900 text-sm">Francisca López</p>
                            <p class="text-slate-500 text-xs">Pequeña fábrica textil, Concepción</p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 whitespace-nowrap">Sin Excel</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Pricing -->
<section id="precios" class="py-16 sm:py-20 lg:py-24 bg-white section-spacing">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16 reveal">
            <h2 class="text-slate-900">Planes que crecen con tu <span class="text-brand-600">negocio</span></h2>
            <p class="mt-5 text-lg text-slate-600 leading-relaxed">Todos los planes incluyen 14 días gratis. Sin tarjeta de crédito. Sin compromiso.</p>
        </div>
        <div class="grid md:grid-cols-3 gap-8 lg:gap-10 max-w-5xl mx-auto items-center">
            <!-- Plan 1: Emprendedor -->
            <div class="bezel-outer bezel-card reveal">
                <div class="bezel-inner">
                    <div class="text-center mb-6">
                        <h3 class="text-lg font-semibold text-slate-900">Emprendedor</h3>
                        <div class="mt-3">
                            <span class="text-4xl font-bold text-slate-900">$29.990</span>
                            <span class="text-slate-500 text-sm">/mes</span>
                        </div>
                        <p class="text-slate-400 text-xs mt-1">~0,8 UF</p>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start gap-2 text-sm text-slate-700">
                            <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            Hasta 50 productos
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
                    <a href="#precios" class="btn-secondary w-full inline-flex items-center justify-center font-semibold rounded-full text-sm px-5 py-3.5 bg-white text-slate-700 border border-slate-200 hover:bg-slate-50 hover:border-slate-300 shadow-refined-sm">Empezar gratis</a>
                </div>
            </div>
            <!-- Plan 2: Productor (Popular) — Z-Axis Cascade -->
            <div class="pricing-popular-wrapper pricing-popular-emphasis reveal reveal-delay-1">
                <div class="bezel-outer pricing-popular" style="background: rgba(236, 253, 245, 0.5); border-color: rgba(16, 185, 129, 0.3);">
                    <div class="bezel-inner relative">
                        <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                            <span class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-bold bg-brand-600 text-white shadow-brand-lg tracking-wide">Más popular</span>
                        </div>
                        <div class="text-center mb-6 pt-2">
                            <h3 class="text-lg font-semibold text-slate-900">Productor</h3>
                            <div class="mt-3">
                                <span class="text-4xl font-bold text-slate-900">$59.990</span>
                                <span class="text-slate-500 text-sm">/mes</span>
                            </div>
                            <p class="text-slate-400 text-xs mt-1">~1,6 UF</p>
                        </div>
                        <ul class="space-y-3 mb-8">
                            <li class="flex items-start gap-2 text-sm text-slate-700">
                                <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                Productos ilimitados
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
                        <a href="#precios" class="btn-premium w-full inline-flex items-center justify-center font-semibold rounded-full text-lg px-8 py-4 bg-brand-600 text-white hover:bg-brand-700 shadow-brand-lg hover:shadow-brand-xl">Empezar gratis</a>
                    </div>
                </div>
            </div>
            <!-- Plan 3: Empresa -->
            <div class="bezel-outer bezel-card reveal reveal-delay-2">
                <div class="bezel-inner">
                    <div class="text-center mb-6">
                        <h3 class="text-lg font-semibold text-slate-900">Empresa</h3>
                        <div class="mt-3">
                            <span class="text-4xl font-bold text-slate-900">$99.990</span>
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
                    <a href="https://wa.me/<?= htmlspecialchars($contactWhatsApp, ENT_QUOTES, 'UTF-8') ?>?text=<?= rawurlencode('Hola, me interesa el plan Empresa de ' . $appName . '. Quiero hablar con ventas.') ?>" target="_blank" rel="noopener" class="btn-secondary w-full inline-flex items-center justify-center font-semibold rounded-full text-sm px-5 py-3.5 bg-white text-slate-700 border border-slate-200 hover:bg-slate-50 hover:border-slate-300 shadow-refined-sm">Hablar con ventas</a>
                </div>
            </div>
        </div>
        <p class="text-center text-xs text-slate-400 mt-8">Precios en pesos chilenos, sujetos a variación UF.</p>
    </div>
</section>

<!-- FAQ -->
<section id="faq" class="py-16 sm:py-20 section-slate-tint">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-14 sm:mb-16 reveal">
            <h2 class="text-slate-900">Preguntas <span class="text-brand-600">frecuentes</span></h2>
        </div>
        <div class="max-w-3xl mx-auto space-y-4">
            <!-- FAQ 1 -->
            <details class="group bezel-outer bezel-card reveal faq-open">
                <summary class="flex items-center justify-between cursor-pointer px-6 py-5 text-base font-semibold text-slate-900 hover:text-brand-600 transition-colors duration-350 list-none" style="transition-timing-function: var(--ease-premium)">
                    ¿Qué es <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> y para qué sirve?
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-350 shrink-0 ml-4" style="transition-timing-function: var(--ease-premium)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </summary>
                <div class="px-6 pb-5 text-sm text-slate-600 leading-relaxed">
                    <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> es un software de gestión para pequeños fabricantes y productores que te permite calcular el costo real de tus productos a partir de sus insumos, controlar tu inventario, gestionar ventas y emitir facturación electrónica. Todo en un solo lugar.
                </div>
            </details>
            <!-- FAQ 2 -->
            <details class="group bezel-outer bezel-card reveal reveal-delay-1 faq-open">
                <summary class="flex items-center justify-between cursor-pointer px-6 py-5 text-base font-semibold text-slate-900 hover:text-brand-600 transition-colors duration-350 list-none" style="transition-timing-function: var(--ease-premium)">
                    ¿Cómo se calcula el costo de producción?
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-350 shrink-0 ml-4" style="transition-timing-function: var(--ease-premium)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </summary>
                <div class="px-6 pb-5 text-sm text-slate-600 leading-relaxed">
                    Defines un producto con los insumos y cantidades que usas. <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> toma el costo de cada insumo de tu inventario, le suma la mano de obra, los gastos fijos que asignes y la merma del proceso. El resultado es el costo real por unidad producida.
                </div>
            </details>
            <!-- FAQ 3 -->
            <details class="group bezel-outer bezel-card reveal reveal-delay-2 faq-open">
                <summary class="flex items-center justify-between cursor-pointer px-6 py-5 text-base font-semibold text-slate-900 hover:text-brand-600 transition-colors duration-350 list-none" style="transition-timing-function: var(--ease-premium)">
                    ¿Para qué tipo de negocios sirve?
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-350 shrink-0 ml-4" style="transition-timing-function: var(--ease-premium)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </summary>
                <div class="px-6 pb-5 text-sm text-slate-600 leading-relaxed">
                    Para cualquier negocio que transforme insumos en un producto terminado: manufactura, cosmética, velas, muebles, textiles, alimentos o producción artesanal. Si combinas materiales para fabricar algo, <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> te sirve.
                </div>
            </details>
            <!-- FAQ 4 -->
            <details class="group bezel-outer bezel-card reveal faq-open">
                <summary class="flex items-center justify-between cursor-pointer px-6 py-5 text-base font-semibold text-slate-900 hover:text-brand-600 transition-colors duration-350 list-none" style="transition-timing-function: var(--ease-premium)">
                    ¿Puedo emitir facturas electrónicas en Chile?
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-350 shrink-0 ml-4" style="transition-timing-function: var(--ease-premium)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </summary>
                <div class="px-6 pb-5 text-sm text-slate-600 leading-relaxed">
                    Sí. <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> está integrado con el SII de Chile para emitir boletas, facturas y guías de despacho electrónicas directamente desde la plataforma. No necesitas software adicional.
                </div>
            </details>
            <!-- FAQ 5 -->
            <details class="group bezel-outer bezel-card reveal reveal-delay-1 faq-open">
                <summary class="flex items-center justify-between cursor-pointer px-6 py-5 text-base font-semibold text-slate-900 hover:text-brand-600 transition-colors duration-350 list-none" style="transition-timing-function: var(--ease-premium)">
                    ¿Tiene período de prueba gratis?
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-350 shrink-0 ml-4" style="transition-timing-function: var(--ease-premium)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </summary>
                <div class="px-6 pb-5 text-sm text-slate-600 leading-relaxed">
                    Sí. Todos los planes incluyen 14 días de prueba gratis, sin necesidad de tarjeta de crédito. Puedes explorar todas las funcionalidades antes de decidir.
                </div>
            </details>
            <!-- FAQ 6 -->
            <details class="group bezel-outer bezel-card reveal reveal-delay-2 faq-open">
                <summary class="flex items-center justify-between cursor-pointer px-6 py-5 text-base font-semibold text-slate-900 hover:text-brand-600 transition-colors duration-350 list-none" style="transition-timing-function: var(--ease-premium)">
                    ¿Qué es la merma y por qué importa?
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-350 shrink-0 ml-4" style="transition-timing-function: var(--ease-premium)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </summary>
                <div class="px-6 pb-5 text-sm text-slate-600 leading-relaxed">
                    La merma es la pérdida de materia prima durante el proceso de producción (evaporación, desperdicio, errores). <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> la calcula automáticamente para que conozcas el costo real de cada producto y no pierdas plata sin saberlo.
                </div>
            </details>
            <!-- FAQ 7 -->
            <details class="group bezel-outer bezel-card reveal faq-open">
                <summary class="flex items-center justify-between cursor-pointer px-6 py-5 text-base font-semibold text-slate-900 hover:text-brand-600 transition-colors duration-350 list-none" style="transition-timing-function: var(--ease-premium)">
                    ¿<?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> funciona para negocios fuera de Chile?
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-350 shrink-0 ml-4" style="transition-timing-function: var(--ease-premium)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </summary>
                <div class="px-6 pb-5 text-sm text-slate-600 leading-relaxed">
                    Sí. Aunque la facturación electrónica está optimizada para Chile, el costeo de productos, inventario y gestión de ventas funcionan para cualquier país. Puedes usar <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> en cualquier moneda.
                </div>
            </details>
        </div>
    </div>
</section>

<!-- Final CTA -->
<section class="section-dark-cta text-white section-divider-strong" style="padding-block: clamp(7rem, 11vw, 11rem);">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto">
            <h2 class="text-white reveal">
                Deja de adivinar. <span class="text-brand-400">Empieza a saber.</span>
            </h2>
            <p class="mt-6 text-lg sm:text-xl text-slate-300 leading-relaxed reveal reveal-delay-1">
                Cada día que pasas sin conocer tu margen real es un día que puedes estar perdiendo plata. Prueba <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> gratis durante 14 días y conoce tu ganancia de verdad.
            </p>

            <!-- Stats -->
            <div class="mt-16 grid grid-cols-3 gap-8 reveal reveal-delay-1">
                <div class="text-center">
                    <div class="text-3xl sm:text-4xl font-bold text-brand-400">500+</div>
                    <div class="text-sm text-slate-400 mt-1">Negocios activos</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl sm:text-4xl font-bold text-brand-400">34%</div>
                    <div class="text-sm text-slate-400 mt-1">Margen promedio</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl sm:text-4xl font-bold text-brand-400">14 días</div>
                    <div class="text-sm text-slate-400 mt-1">Prueba gratis</div>
                </div>
            </div>

            <!-- Benefits list -->
            <div class="mt-10 flex flex-wrap justify-center gap-x-8 gap-y-3 reveal reveal-delay-1">
                <div class="flex items-center gap-2 text-sm text-slate-300">
                    <svg class="w-5 h-5 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    Sin tarjeta de crédito
                </div>
                <div class="flex items-center gap-2 text-sm text-slate-300">
                    <svg class="w-5 h-5 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    Configuración en 5 minutos
                </div>
                <div class="flex items-center gap-2 text-sm text-slate-300">
                    <svg class="w-5 h-5 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    Soporte en español
                </div>
            </div>

            <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center reveal reveal-delay-2">
                <a href="#" data-trial-open class="btn-primary-large btn-premium inline-flex items-center justify-center font-semibold rounded-full text-white group">
                    Empezar gratis — sin tarjeta
                    <span class="btn-icon ml-2 inline-flex">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                    </span>
                </a>
                <a href="https://wa.me/<?= htmlspecialchars($contactWhatsApp, ENT_QUOTES, 'UTF-8') ?>" class="btn-premium inline-flex items-center justify-center px-8 py-4 rounded-full border border-slate-600 text-white text-base font-semibold hover:bg-slate-800">
                    <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    Hablar por WhatsApp
                </a>
            </div>
        </div>
    </div>
</section>
</main>

<!-- Footer -->
<footer class="bg-slate-950 text-slate-400">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
            <!-- Brand Column -->
            <div class="md:col-span-5">
                <div class="flex items-center gap-2.5 mb-4">
                    <img src="<?= $brandLogo ? htmlspecialchars($brandLogo, ENT_QUOTES, 'UTF-8') : '/assets/logo-icon.svg' ?>" alt="<?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?>" class="w-8 h-8" style="width:1.75rem;height:1.75rem;">
                    <span class="text-base font-bold text-white"><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <p class="text-sm text-slate-400 leading-relaxed max-w-sm mb-5">
                    Conoce el costo real de cada producto que fabricas. Calcula tu margen de ganancia de verdad.
                </p>
                <div class="flex items-center gap-4">
                    <?php if ($socialInstagram): ?>
                    <a href="<?= htmlspecialchars($socialInstagram, ENT_QUOTES, 'UTF-8') ?>" class="text-slate-400 hover:text-brand-400 transition-colors" aria-label="Instagram">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                    </a>
                    <?php endif; ?>
                    <?php if ($socialLinkedin): ?>
                    <a href="<?= htmlspecialchars($socialLinkedin, ENT_QUOTES, 'UTF-8') ?>" class="text-slate-400 hover:text-brand-400 transition-colors" aria-label="LinkedIn">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    </a>
                    <?php endif; ?>
                    <a href="https://youtube.com/@ottertech308" class="text-slate-400 hover:text-brand-400 transition-colors" aria-label="YouTube">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                    </a>
                </div>
            </div>

            <!-- Product Column -->
            <div class="md:col-span-3">
                <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Producto</h3>
                <ul class="space-y-3">
                    <li><a href="#producto" class="text-sm hover:text-brand-400 transition-colors">Características</a></li>
                    <li><a href="#precios" class="text-sm hover:text-brand-400 transition-colors">Precios</a></li>
                    <li><a href="#calculadora" class="text-sm hover:text-brand-400 transition-colors">Calculadora gratuita</a></li>
                    <li><a href="#faq" class="text-sm hover:text-brand-400 transition-colors">Preguntas frecuentes</a></li>
                    <li><a href="#" data-client-open class="text-sm hover:text-brand-400 transition-colors">Ya soy cliente</a></li>
                </ul>
            </div>

            <!-- Contact Column -->
            <div class="md:col-span-4">
                <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Contacto</h3>
                <ul class="space-y-3">
                    <?php if (!empty($contactEmail)): ?>
                    <li>
                        <a href="mailto:<?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8') ?>" class="text-sm hover:text-brand-400 transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (!empty($contactPhone)): ?>
                    <li>
                        <a href="tel:<?= htmlspecialchars($contactPhone, ENT_QUOTES, 'UTF-8') ?>" class="text-sm hover:text-brand-400 transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            <?= htmlspecialchars($contactPhone, ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li>
                        <a href="https://wa.me/<?= htmlspecialchars($contactWhatsApp, ENT_QUOTES, 'UTF-8') ?>" class="text-sm hover:text-brand-400 transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-500" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            WhatsApp
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="border-t border-slate-800 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center gap-4" style="margin-top:2.5rem;padding-top:1.5rem;">
            <p class="text-xs text-slate-500">&copy; <?= date('Y') ?> <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?>. Todos los derechos reservados. Hecho en Chile.</p>
            <div class="flex items-center gap-6">
                <a href="#" title="Próximamente" class="text-xs text-slate-500 hover:text-slate-400 transition-colors">Términos</a>
                <a href="#" title="Próximamente" class="text-xs text-slate-500 hover:text-slate-400 transition-colors">Privacidad</a>
                <a href="#" data-cookie-open class="text-xs text-slate-500 hover:text-slate-400 transition-colors">Preferencias de cookies</a>
            </div>
        </div>
    </div>
</footer>

<!-- ============ Modal "Ya soy cliente" ============ -->
<div class="client-modal" id="client-modal" role="dialog" aria-modal="true" aria-labelledby="client-modal-title" aria-hidden="true"
     data-base-domain="<?= htmlspecialchars($baseDomain, ENT_QUOTES, 'UTF-8') ?>"
     data-scheme="<?= htmlspecialchars($scheme, ENT_QUOTES, 'UTF-8') ?>"
     data-scroll-contact="<?= ($formSuccess || $formError !== '') ? '1' : '0' ?>">
    <div class="client-modal-box">
        <h3 id="client-modal-title">Ingresa a tu cuenta</h3>
        <p>Cada cliente tiene su propio subdominio. Escribe el de tu empresa y te llevamos al acceso.</p>
        <div class="client-modal-row">
            <label class="client-modal-sr" for="client-slug">Subdominio</label>
            <input type="text" id="client-slug" placeholder="tu-empresa" autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false">
            <span class="client-modal-suffix">.<?= htmlspecialchars($baseDomain, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="client-error" id="client-error" aria-live="polite"></div>
        <div class="client-modal-actions">
            <button type="button" class="client-modal-btn client-modal-btn-ghost" id="client-cancel">Cancelar</button>
            <button type="button" class="client-modal-btn client-modal-btn-primary" id="client-go">Ir a mi cuenta</button>
        </div>
    </div>
</div>

<!-- ============ Modal "Solicita tu prueba" (captura de lead) ============ -->
<div class="client-modal" id="trial-modal" role="dialog" aria-modal="true" aria-labelledby="trial-modal-title" aria-hidden="true">
    <div class="client-modal-box" style="max-width: 460px;">
        <form id="trial-form" novalidate>
            <input type="hidden" name="action" value="lead">
            <!-- Honeypot — debe quedar oculto -->
            <input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;opacity:0;height:0;width:0">

            <h3 id="trial-modal-title">Solicita tu prueba gratis</h3>
            <p>Déjanos tus datos y te contactamos para activar tus 14 días — sin tarjeta de crédito.</p>

            <div class="space-y-4">
                <div>
                    <label for="trial-nombre" class="block text-xs font-medium text-slate-600 mb-1.5">Nombre</label>
                    <input type="text" id="trial-nombre" name="nombre" required autocomplete="name"
                           class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-slate-50/50 focus:bg-white"
                           placeholder="Tu nombre">
                </div>
                <div>
                    <label for="trial-empresa" class="block text-xs font-medium text-slate-600 mb-1.5">Empresa</label>
                    <input type="text" id="trial-empresa" name="empresa" required autocomplete="organization"
                           class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-slate-50/50 focus:bg-white"
                           placeholder="Nombre de tu empresa">
                </div>
                <div>
                    <label for="trial-email" class="block text-xs font-medium text-slate-600 mb-1.5">Correo electrónico</label>
                    <input type="email" id="trial-email" name="email" required autocomplete="email"
                           class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-slate-50/50 focus:bg-white"
                           placeholder="tu@email.com">
                </div>
                <div>
                    <label for="trial-telefono" class="block text-xs font-medium text-slate-600 mb-1.5">Teléfono <span class="text-slate-400 font-normal">(opcional)</span></label>
                    <input type="tel" id="trial-telefono" name="telefono" autocomplete="tel"
                           class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-slate-50/50 focus:bg-white"
                           placeholder="+56 9 ...">
                </div>
            </div>

            <div class="client-error" id="trial-error" aria-live="polite"></div>

            <div class="client-modal-actions">
                <button type="button" class="client-modal-btn client-modal-btn-ghost" id="trial-cancel">Cancelar</button>
                <button type="submit" class="client-modal-btn client-modal-btn-primary" id="trial-submit">Enviar solicitud</button>
            </div>
        </form>

        <div id="trial-success" style="display:none; text-align:center; padding: 12px 0 6px;">
            <div style="margin:0 auto 14px; width:48px; height:48px; border-radius:9999px; background:#d1fae5; color:#059669; display:flex; align-items:center; justify-content:center;">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h3 style="margin:0 0 6px;">¡Gracias! Recibimos tu solicitud.</h3>
            <p style="margin:0 0 16px;">Te contactaremos muy pronto para activar tu prueba.</p>
            <div class="client-modal-actions">
                <button type="button" class="client-modal-btn client-modal-btn-primary" id="trial-close">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== Banner de cookies (consentimiento opt-in) ===== -->
<div class="cookie-banner" id="cookie-banner" role="region" aria-label="Aviso de cookies">
    <div class="cookie-banner-inner">
        <div class="cookie-banner-text">
            Usamos cookies propias y de terceros para que el sitio funcione y, solo con tu permiso, para analítica y marketing.
            Puedes aceptarlas todas, rechazarlas o elegir cuáles permitir. Tu decisión se puede cambiar cuando quieras desde «Preferencias de cookies» en el pie de página.
        </div>
        <div class="cookie-banner-actions">
            <button type="button" class="cookie-btn cookie-btn-link" id="cookie-customize">Personalizar</button>
            <button type="button" class="cookie-btn cookie-btn-ghost" id="cookie-reject">Rechazar</button>
            <button type="button" class="cookie-btn cookie-btn-primary" id="cookie-accept">Aceptar</button>
        </div>
    </div>
</div>

<!-- ===== Modal de preferencias de cookies (control granular + retracto) ===== -->
<div class="client-modal" id="cookie-prefs" role="dialog" aria-modal="true" aria-labelledby="cookie-prefs-title" aria-hidden="true">
    <div class="client-modal-box" style="max-width: 520px;">
        <h3 id="cookie-prefs-title">Preferencias de cookies</h3>
        <p>Elige qué cookies permites. Las estrictamente necesarias siempre están activas; las demás solo se instalan si las aceptas.</p>

        <div class="cookie-cat">
            <div>
                <h4>Estrictamente necesarias</h4>
                <p>Imprescindibles para el funcionamiento del sitio y para recordar tu elección de cookies. No se pueden desactivar.</p>
            </div>
            <label class="cookie-switch"><input type="checkbox" checked disabled aria-label="Cookies necesarias (siempre activas)"><span></span></label>
        </div>
        <div class="cookie-cat">
            <div>
                <h4>Analíticas</h4>
                <p>Nos ayudan a entender cómo se usa el sitio para mejorarlo (medición de uso).</p>
            </div>
            <label class="cookie-switch"><input type="checkbox" id="cookie-cat-analytics" aria-label="Cookies analíticas"><span></span></label>
        </div>
        <div class="cookie-cat">
            <div>
                <h4>Marketing y publicidad</h4>
                <p>Permiten mostrarte contenido y anuncios relevantes (remarketing y redes sociales).</p>
            </div>
            <label class="cookie-switch"><input type="checkbox" id="cookie-cat-marketing" aria-label="Cookies de marketing"><span></span></label>
        </div>

        <div class="client-modal-actions" style="flex-wrap: wrap;">
            <button type="button" class="client-modal-btn client-modal-btn-ghost" id="cookie-prefs-reject">Rechazar todas</button>
            <button type="button" class="client-modal-btn client-modal-btn-ghost" id="cookie-prefs-save">Guardar preferencias</button>
            <button type="button" class="client-modal-btn client-modal-btn-primary" id="cookie-prefs-accept">Aceptar todas</button>
        </div>
    </div>
</div>

<script src="/assets/calculator.js" defer></script>
<script src="/assets/landing.js" defer></script>

</body>
</html>
