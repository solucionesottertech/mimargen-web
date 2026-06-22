<?php
/**
 * MiMargen Landing Page
 *
 * Single-file PHP entry point for the apex domain (mimargen.cl).
 * Bootstraps the PHP backend, injects PlatformSettings variables,
 * generates canonical/OG meta tags, and handles honeypot lead-capture.
 */

require_once __DIR__ . '/core/bootstrap.php';

// Load platform settings (OtterErp contract: requires rootDataDir)
$rootDataDir = dirname(__DIR__) . '/data';
$settings    = PlatformSettings::load($rootDataDir);

// Extract settings with fallbacks
$heroTitle       = $settings['hero_title']       ?? 'Conoce cuánto ganas realmente con cada producto';
$heroLead        = $settings['hero_lead']        ?? 'Crea recetas con tus ingredientes, calcula el costo real de producción —incluyendo merma— y conoce tu margen de ganancia real. Todo en un solo lugar, sin hojas de cálculo que no te cierran.';
$contactEmail    = $settings['contact_email']    ?? null;
$contactPhone    = $settings['contact_phone']    ?? '+56 9 0000 0000';
$contactWhatsApp = $settings['contact_whatsapp'] ?? '56900000000';
$contactCity     = $settings['contact_city']     ?? 'Santiago, Chile';
$socialLinkedin  = $settings['social_linkedin']  ?? null;
$socialInstagram = $settings['social_instagram'] ?? null;

// Logo (separate methods, not in load())
$brandLogo = PlatformSettings::brandLogoDataUrl($rootDataDir);

// Canonical URL
$scheme    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'] ?? (defined('BASE_DOMAIN') ? BASE_DOMAIN : 'mimargen.cl');
$canonical = $scheme . '://' . $host . '/';

// Page meta (hardcoded — not in PlatformSettings whitelist)
$metaTitle       = 'MiMargen · Calcula el costo y margen real de tus recetas';
$metaDescription = 'Calcula tu margen de ganancia de verdad. Costeo por receta, merma, mano de obra y precio de venta. Diseñado para emprendedores y pequeños productores en Chile.';

// ── Handle POST form submission (honeypot + lead capture) ──────
// Contract: must match OtterErp landing.php handler exactly
$formError    = '';
$formSuccess  = false;
$formNombre   = '';
$formEmpresa  = '';
$formEmail    = '';
$formTelefono = '';
$formMensaje  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'lead') {

    // Honeypot — if bot fills this, silently discard
    if (!empty($_POST['website'] ?? '')) {
        $formSuccess = true;
    } else {
        $formNombre   = trim((string)($_POST['nombre']   ?? ''));
        $formEmpresa  = trim((string)($_POST['empresa']  ?? ''));
        $formEmail    = trim((string)($_POST['email']    ?? ''));
        $formTelefono = trim((string)($_POST['telefono'] ?? ''));
        $formMensaje  = trim((string)($_POST['mensaje']  ?? ''));

        // Server-side validation (matches OtterErp contract)
        if (mb_strlen($formNombre) < 2) {
            $formError = 'Cuéntanos tu nombre.';
        } elseif (mb_strlen($formEmpresa) < 2) {
            $formError = 'Indica el nombre de tu empresa.';
        } elseif (!filter_var($formEmail, FILTER_VALIDATE_EMAIL)) {
            $formError = 'Necesitamos un correo válido para escribirte.';
        } else {
            try {
                $platformDir = dirname(__DIR__) . '/data/_platform';
                if (!is_dir($platformDir)) {
                    @mkdir($platformDir, 0750, true);
                }
                $enc     = new Encryption(APP_SECRET);
                $storage = new JsonStorage($platformDir, $enc);
                $storage->insert('leads', [
                    'nombre'     => $formNombre,
                    'empresa'    => $formEmpresa,
                    'email'      => $formEmail,
                    'telefono'   => $formTelefono,
                    'mensaje'    => $formMensaje,
                    'ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
                    'user_agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                    'referer'    => substr((string)($_SERVER['HTTP_REFERER']    ?? ''), 0, 255),
                    'created_at' => date('c'),
                ]);
                $formSuccess = true;
            } catch (Throwable $e) {
                $displayEmail = $contactEmail ?? 'contacto@' . $host;
                $formError = 'No pudimos guardar tu solicitud. Escríbenos directo a ' . htmlspecialchars($displayEmail) . '.';
            }
        }
    }
}

// JSON-LD Schema
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'SoftwareApplication',
    'name' => 'MiMargen',
    'description' => 'Software de gestión para pequeños productores con costeo por receta, inventario, ventas y facturación electrónica.',
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
    <meta name="keywords" content="calcular costo de producción, margen de ganancia, costeo por receta, software inventario, ERP pymes Chile, control de stock, facturación electrónica, MiMargen">
    <meta name="author" content="MiMargen">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:title" content="<?= htmlspecialchars($metaTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image" content="https://mimargen.cl/assets/og-image.png">
    <meta property="og:locale" content="es_CL">
    <meta property="og:site_name" content="MiMargen">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?= htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:title" content="<?= htmlspecialchars($metaTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:image" content="https://mimargen.cl/assets/og-image.png">

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
           PREMIUM CSS ARCHITECTURE — MiMargen Landing
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
        .section-spacing { margin-bottom: 6rem; }
        @media (min-width: 768px) { .section-spacing { margin-bottom: 8rem; } }

        /* ── Better button padding ───────────────────────── */
        .btn-secondary-fixed { padding: 0.875rem 2rem !important; min-width: 200px; }

        /* ── Header solid on scroll ──────────────────────── */
        .header-solid { background: white !important; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border-bottom-color: rgba(203, 213, 225, 0.4) !important; }

        /* ── Double-Bezel Card Architecture (Stitch-subtle) ── */
        .bezel-outer {
            background: rgba(248, 250, 252, 0.5);
            border: 1px solid rgba(203, 213, 225, 0.4);
            border-radius: 1.75rem;
            padding: 0.5rem;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.02);
        }
        .bezel-inner {
            background: white;
            border-radius: 1.25rem;
            box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.9), 0 1px 3px rgba(0, 0, 0, 0.04);
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
        .section-brand-tint { background-color: rgba(236, 253, 245, 0.25); margin-top: 4rem; margin-bottom: 4rem; border-radius: 2rem; }
        .section-slate-tint { background-color: rgba(248, 250, 252, 0.5); margin-top: 4rem; margin-bottom: 4rem; border-radius: 2rem; }
        .section-dark-cta { background: linear-gradient(160deg, #0f172a 0%, #1a2636 40%, #0f172a 100%); }

        /* ── Pricing Popular (Z-Axis Cascade) ────────────── */
        .pricing-popular-wrapper {
            transform: scale(1.08);
            z-index: 10;
            transition: transform 350ms var(--ease-premium), box-shadow 350ms var(--ease-premium);
        }
        .pricing-popular-wrapper:hover {
            transform: scale(1.08) translateY(-4px);
            box-shadow: 0 20px 40px rgba(16, 185, 129, 0.15);
        }
        @media (max-width: 767px) {
            .pricing-popular-wrapper { transform: scale(1); }
            .pricing-popular-wrapper:hover { transform: translateY(-4px); }
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
            font-size: 1rem;
            padding: 0.875rem 1.75rem;
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.25), 0 4px 10px rgba(16, 185, 129, 0.15);
            transition: all 400ms var(--ease-premium);
        }
        .btn-primary-large:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 32px rgba(16, 185, 129, 0.3), 0 6px 16px rgba(16, 185, 129, 0.18);
        }
        .btn-primary-large:active {
            transform: translateY(-1px) scale(0.98);
        }

        /* ── Stronger Double-Bezel (now subtle Stitch) ──── */
        .bezel-outer-strong {
            background: rgba(248, 250, 252, 0.5);
            border: 1px solid rgba(203, 213, 225, 0.4);
            border-radius: 1.75rem;
            padding: 0.5rem;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.02);
        }
        .bezel-inner-strong {
            background: white;
            border-radius: 1.25rem;
            box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.9), 0 1px 3px rgba(0, 0, 0, 0.04);
            padding: 2rem;
        }

        /* ── Pricing Popular Emphasis ───────────────────── */
        .pricing-popular-emphasis {
            transform: scale(1.08);
            box-shadow: 0 25px 50px rgba(16, 185, 129, 0.15), 0 0 0 1px rgba(16, 185, 129, 0.2);
            border-radius: 1.75rem;
            overflow: hidden;
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
            min-height: 220px;
            display: flex;
            flex-direction: column;
            justify-content: center;
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

        /* ── Stronger card lift on hover ── */
        .bezel-card:hover {
            box-shadow: 0 16px 40px rgba(16,185,129,0.12), 0 4px 10px rgba(16,185,129,0.05);
        }

        /* ── Comparison table: highlight the MiMargen column ── */
        .comparison-table td:nth-child(2),
        .comparison-table th:nth-child(2) {
            background: rgba(16,185,129,0.06);
            box-shadow: inset 1px 0 0 rgba(16,185,129,0.14), inset -1px 0 0 rgba(16,185,129,0.14);
        }
        .comparison-table thead th:nth-child(2) {
            background: rgba(16,185,129,0.14);
            box-shadow: inset 1px 0 0 rgba(16,185,129,0.18), inset -1px 0 0 rgba(16,185,129,0.18);
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
    </style>
</head>
<body class="font-sans antialiased text-slate-800 bg-white">

<!-- Header -->
<header id="site-header" class="fixed top-0 left-0 right-0 z-50 bg-white/0 border-b border-transparent transition-all duration-300">
    <nav class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <!-- Logo -->
        <a href="#" class="flex items-center gap-2.5 group">
            <img src="/assets/logo-icon.svg" alt="MiMargen" class="w-8 h-8 group-hover:scale-105 transition-all duration-350" style="transition-timing-function: var(--ease-premium)">
            <span class="text-lg font-bold text-slate-900 tracking-tight">Mi<span class="text-brand-600">Margen</span></span>
        </a>

        <!-- Desktop Nav -->
        <div class="hidden md:flex items-center gap-8">
            <a href="#producto" class="text-sm font-medium text-slate-600 hover:text-brand-600 transition-colors duration-350" style="transition-timing-function: var(--ease-premium)">Producto</a>
            <a href="#precios" class="text-sm font-medium text-slate-600 hover:text-brand-600 transition-colors duration-350" style="transition-timing-function: var(--ease-premium)">Precios</a>
            <a href="#calculadora" class="text-sm font-medium text-slate-600 hover:text-brand-600 transition-colors duration-350" style="transition-timing-function: var(--ease-premium)">Calculadora</a>
            <a href="#faq" class="text-sm font-medium text-slate-600 hover:text-brand-600 transition-colors duration-350" style="transition-timing-function: var(--ease-premium)">FAQ</a>
            <a href="#precios" class="ml-2 btn-premium btn-nav inline-flex items-center rounded-full bg-brand-600 text-white text-sm font-semibold hover:bg-brand-700 shadow-brand-md hover:shadow-brand-lg">Pruébalo gratis</a>
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
            <a href="#precios" class="block text-center px-4 py-2.5 rounded-full bg-brand-600 text-white text-sm font-semibold hover:bg-brand-700 transition-colors">Pruébalo gratis</a>
        </div>
    </div>
</header>

<main>
<!-- Hero -->
<section class="relative pt-32 pb-20 sm:pt-40 sm:pb-28 lg:pt-44 lg:pb-36 overflow-hidden section-hero-gradient section-divider-fade">
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
                    Conoce cuánto ganas <span class="text-brand-600 hl-underline">realmente</span> con cada producto
                </h1>
                <p class="mt-8 text-lg sm:text-xl text-slate-600 leading-relaxed max-w-xl mx-auto lg:mx-0">
                    <?= htmlspecialchars($heroLead, ENT_QUOTES, 'UTF-8') ?>
                </p>
                <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    <a href="#precios" class="btn-primary-large btn-premium inline-flex items-center justify-center font-semibold rounded-full text-white group">
                        Pruébalo gratis 14 días
                        <span class="btn-icon ml-2 inline-flex">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                        </span>
                    </a>
                    <a href="#como-funciona" class="btn-secondary inline-flex items-center justify-center font-medium rounded-full text-sm py-2.5 px-5 bg-white text-slate-700 border border-slate-200 hover:bg-slate-50 hover:border-slate-300 shadow-refined-sm">
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
            <div>
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
                                   value="<?= htmlspecialchars($formNombre ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-350 bg-slate-50/50 focus:bg-white"
                                   style="transition-timing-function: var(--ease-premium)"
                                   placeholder="Tu nombre">
                        </div>
                        <div>
                            <label for="lead-empresa" class="block text-xs font-medium text-slate-600 mb-1.5">Empresa</label>
                            <input type="text" id="lead-empresa" name="empresa" required
                                   value="<?= htmlspecialchars($formEmpresa ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-350 bg-slate-50/50 focus:bg-white"
                                   style="transition-timing-function: var(--ease-premium)"
                                   placeholder="Nombre de tu empresa">
                        </div>
                        <div>
                            <label for="lead-email" class="block text-xs font-medium text-slate-600 mb-1.5">Correo electrónico</label>
                            <input type="email" id="lead-email" name="email" required
                                   value="<?= htmlspecialchars($formEmail ?? '', ENT_QUOTES, 'UTF-8') ?>"
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
<section id="producto" class="py-24 sm:py-32 bg-white section-spacing">
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
                    <p class="text-slate-600 text-sm leading-relaxed">Sumas los ingredientes, le multiplicas por dos y esperas que alcance. Pero nunca sabes si realmente estás ganando o perdiendo plata.</p>
                </div>
            </div>
            <!-- Card 2 — Double-Bezel minimal -->
                    <div class="bezel-outer bezel-card reveal reveal-delay-1 pain-card-minimal">
                <div class="bezel-inner text-center">
                    <div class="icon-minimal-wrap">
                        <svg class="icon-minimal mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M3 14h18M3 6h18M3 18h18M10 3v18M14 3v18" /></svg>
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
<section id="como-funciona" class="py-24 sm:py-32 lg:py-40 section-brand-tint section-spacing">
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
                        <h3 class="text-lg font-semibold text-slate-900 mb-2">Crea tu receta</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">Agrega los ingredientes o materiales que usas, con sus cantidades exactas. Puedes usar productos de tu inventario o crear insumos nuevos.</p>
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
                        <p class="text-slate-600 text-sm leading-relaxed">MiMargen suma automáticamente el costo de materiales, mano de obra, gastos fijos y la merma que generas en el proceso. Sin fórmulas, sin errores.</p>
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
<section id="features" class="py-24 sm:py-32 bg-white section-spacing">
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
                    <h3 class="text-base font-semibold text-slate-900 mb-1.5">Costeo por receta</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Calcula el costo real de cada producto incluyendo materiales, mano de obra, merma y gastos fijos.</p>
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
                    <p class="text-slate-600 text-sm leading-relaxed">Emite boletas, facturas y guías de despacho directamente desde MiMargen. Integrado con el SII.</p>
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
                            <th class="text-center py-4 px-6 font-semibold text-brand-600" style="width: 20%">MiMargen</th>
                            <th class="text-center py-4 px-6 font-semibold text-slate-400" style="width: 20%">Excel</th>
                            <th class="text-center py-4 px-6 font-semibold text-slate-400" style="width: 20%">ERPs tradicionales</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr class="hover:bg-slate-50/50 transition-colors duration-350" style="transition-timing-function: var(--ease-premium)">
                            <td class="py-4 px-6 text-slate-700 font-medium">Costeo por receta</td>
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex items-center gap-1.5 text-brand-600 font-semibold text-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    Incluido
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center text-slate-400">Fórmulas manuales</td>
                            <td class="py-4 px-6 text-center text-red-400">No existe</td>
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
                            <td class="py-4 px-6 text-center text-amber-500">A veces</td>
                        </tr>
                        <tr class="hover:bg-slate-50/50 transition-colors duration-350" style="transition-timing-function: var(--ease-premium)">
                            <td class="py-4 px-6 text-slate-700 font-medium">Inventario automático</td>
                            <td class="py-4 px-6 text-center text-brand-600 font-semibold">En tiempo real</td>
                            <td class="py-4 px-6 text-center text-slate-400">Manual</td>
                            <td class="py-4 px-6 text-center text-amber-500">Complejo</td>
                        </tr>
                        <tr class="hover:bg-slate-50/50 transition-colors duration-350" style="transition-timing-function: var(--ease-premium)">
                            <td class="py-4 px-6 text-slate-700 font-medium">Curva de aprendizaje</td>
                            <td class="py-4 px-6 text-center text-brand-600 font-semibold">Horas</td>
                            <td class="py-4 px-6 text-center text-slate-400">—</td>
                            <td class="py-4 px-6 text-center text-red-400">Semanas</td>
                        </tr>
                        <tr class="hover:bg-slate-50/50 transition-colors duration-350" style="transition-timing-function: var(--ease-premium)">
                            <td class="py-4 px-6 text-slate-700 font-medium">Precio accesible</td>
                            <td class="py-4 px-6 text-center text-brand-600 font-semibold">Desde $29.990/mes</td>
                            <td class="py-4 px-6 text-center text-slate-400">"Gratis"</td>
                            <td class="py-4 px-6 text-center text-red-400">$200.000+/mes</td>
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
<section id="para-quien" class="py-24 sm:py-32 section-slate-tint section-spacing">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-14 sm:mb-16 reveal">
            <h2 class="text-slate-900">Hecho para el corazón de la gastronomía</h2>
            <p class="mt-5 text-lg text-slate-600 leading-relaxed">Si transformas materia prima en producto terminado, MiMargen es para ti.</p>
        </div>
        <div class="industry-icon-row reveal">
            <!-- Industry 1: Panaderías -->
            <div class="industry-icon-item">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3c-4 0-7 3-7 7 0 2 1 4 3 5v6h8v-6c2-1 3-3 3-5 0-4-3-7-7-7z M9 21v-2 M15 21v-2" /></svg>
                <span>Panaderías</span>
            </div>
            <!-- Industry 2: Tostadores de café -->
            <div class="industry-icon-item">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 8h1a4 4 0 010 8h-1M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8z M6 1v3M10 1v3M14 1v3" /></svg>
                <span>Tostadores café</span>
            </div>
            <!-- Industry 3: Cosmética natural -->
            <div class="industry-icon-item">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 2h6v4H9V2z M8 6h8v2a4 4 0 01-4 4 4 4 0 01-4-4V6z M12 12v8 M8 20h8" /></svg>
                <span>Cosmética natural</span>
            </div>
            <!-- Industry 4: Chocolaterías -->
            <div class="industry-icon-item">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16v12H4V6z M4 10h16 M4 14h16 M10 6v12 M16 6v12" /></svg>
                <span>Chocolaterías</span>
            </div>
            <!-- Industry 5: Alimentos artesanales -->
            <div class="industry-icon-item">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 2h8v4H8V2z M7 6h10l1 14H6L7 6z M7 10h10" /></svg>
                <span>Alimentos artesanales</span>
            </div>
            <!-- Industry 6: Pequeñas fábricas -->
            <div class="industry-icon-item">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 21h18 M5 21V7l8-4v18 M19 21V11l-6-4 M9 9v.01M9 12v.01M9 15v.01M9 18v.01" /></svg>
                <span>Pequeñas fábricas</span>
            </div>
        </div>
    </div>
</section>

<!-- Calculator -->
<section id="calculadora" class="py-24 sm:py-32 lg:py-40 bg-white section-spacing">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-14 sm:mb-16 reveal">
            <h2 class="text-slate-900">Simula una receta básica</h2>
            <p class="mt-4 text-lg text-slate-600 leading-relaxed">No necesitas registrarte ni dar tu email. Ingresa tus ingredientes, cantidades y precios — y la calculadora te devuelve el costo total.</p>
        </div>
        <div class="max-w-2xl mx-auto reveal">
            <div class="bezel-outer" style="box-shadow: 0 12px 32px rgba(6,95,70,0.08), 0 4px 8px rgba(6,95,70,0.03);">
            <div id="calculator-widget" class="bezel-inner">
                <h3 class="text-xl font-semibold text-slate-900 mb-1.5">Calcula el costo de tu receta</h3>
                <p class="text-sm text-slate-500 mb-6">Ingresa tus ingredientes y obtén el costo real al instante.</p>

                <!-- Rate Limit Notice -->
                <div id="calc-rate-limit-notice" class="hidden mb-6 p-4 rounded-xl bg-amber-50 border border-amber-200">
                    <p class="text-sm text-amber-800 font-medium">Has alcanzado el límite de cálculos gratuitos por hoy.</p>
                    <p class="text-xs text-amber-600 mt-1">
                        <a href="#precios" class="underline font-semibold hover:text-amber-900">Prueba MiMargen gratis</a> para cálculos ilimitados.
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
                            <label class="block text-xs font-medium text-slate-500 mb-1">Ingrediente</label>
                            <input type="text" placeholder="Ej: Harina" class="ing-name w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition" />
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
                            <button type="button" class="remove-ing hidden w-8 h-8 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 transition flex items-center justify-center" aria-label="Eliminar ingrediente">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <button type="button" id="calc-add-ingredient" class="text-sm text-brand-600 hover:text-brand-700 font-medium mb-6 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Añadir ingrediente
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
                <button type="button" id="calc-calculate" class="btn-primary-large btn-premium w-full rounded-full text-white font-semibold">
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
<section id="testimonios" class="py-24 sm:py-32 section-brand-tint section-spacing">
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
                            "Antes pensaba que ganaba $500 por pan. Con MiMargen descubrí que, con la merma y el tiempo de amasado, ganaba $120. Ajusté precios y ahora mi margen real es del 34%. Ojalá lo hubiera usado antes."
                        </blockquote>
                    </div>
                    <div class="border-t border-slate-100 pt-4 flex items-center gap-3">
                        <div class="avatar-initials bg-brand-600">CM</div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-slate-900 text-sm">Carolina Muñoz</p>
                            <p class="text-slate-500 text-xs">Panadería artesanal, Santiago</p>
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
                            "Perdía plata en tres de mis blends y no tenía idea. MiMargen me mostró exactamente cuáles y por qué. En dos semanas ya había corregido los precios. Hoy facturo un 22% más con el mismo volumen."
                        </blockquote>
                    </div>
                    <div class="border-t border-slate-100 pt-4 flex items-center gap-3">
                        <div class="avatar-initials bg-amber-600">DA</div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-slate-900 text-sm">Diego Aravena</p>
                            <p class="text-slate-500 text-xs">Tostador de café, Valparaíso</p>
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
                            "Tenía todo en Excel y era un desastre. Ahora creo la receta, pongo el precio y veo mi margen al instante. Además, las facturas electrónicas me salen directo desde ahí. Me ahorré contratar a alguien más para eso."
                        </blockquote>
                    </div>
                    <div class="border-t border-slate-100 pt-4 flex items-center gap-3">
                        <div class="avatar-initials bg-purple-600">FL</div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-slate-900 text-sm">Francisca López</p>
                            <p class="text-slate-500 text-xs">Cosmética natural, Concepción</p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 whitespace-nowrap">Sin Excel</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Pricing -->
<section id="precios" class="py-24 sm:py-32 lg:py-40 bg-white section-spacing">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-20 reveal">
            <h2 class="text-slate-900">Planes que crecen con tu <span class="text-brand-600">negocio</span></h2>
            <p class="mt-6 text-lg text-slate-600 leading-relaxed">Todos los planes incluyen 14 días gratis. Sin tarjeta de crédito. Sin compromiso.</p>
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
                    <a href="#precios" class="btn-secondary w-full inline-flex items-center justify-center font-semibold rounded-full text-sm px-5 py-3.5 bg-white text-slate-700 border border-slate-200 hover:bg-slate-50 hover:border-slate-300 shadow-refined-sm">Hablar con ventas</a>
                </div>
            </div>
        </div>
        <p class="text-center text-xs text-slate-400 mt-8">Precios en pesos chilenos, sujetos a variación UF.</p>
    </div>
</section>

<!-- FAQ -->
<section id="faq" class="py-24 sm:py-32 section-slate-tint">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-14 sm:mb-16 reveal">
            <h2 class="text-slate-900">Preguntas <span class="text-brand-600">frecuentes</span></h2>
        </div>
        <div class="max-w-3xl mx-auto space-y-4">
            <!-- FAQ 1 -->
            <details class="group bezel-outer bezel-card reveal faq-open">
                <summary class="flex items-center justify-between cursor-pointer px-6 py-5 text-base font-semibold text-slate-900 hover:text-brand-600 transition-colors duration-350 list-none" style="transition-timing-function: var(--ease-premium)">
                    ¿Qué es MiMargen y para qué sirve?
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-350 shrink-0 ml-4" style="transition-timing-function: var(--ease-premium)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </summary>
                <div class="px-6 pb-5 text-sm text-slate-600 leading-relaxed">
                    MiMargen es un software de gestión para pequeños productores que te permite calcular el costo real de tus productos usando recetas, controlar tu inventario, gestionar ventas y emitir facturación electrónica. Todo en un solo lugar.
                </div>
            </details>
            <!-- FAQ 2 -->
            <details class="group bezel-outer bezel-card reveal reveal-delay-1 faq-open">
                <summary class="flex items-center justify-between cursor-pointer px-6 py-5 text-base font-semibold text-slate-900 hover:text-brand-600 transition-colors duration-350 list-none" style="transition-timing-function: var(--ease-premium)">
                    ¿Cómo se calcula el costo de producción con recetas?
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-350 shrink-0 ml-4" style="transition-timing-function: var(--ease-premium)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </summary>
                <div class="px-6 pb-5 text-sm text-slate-600 leading-relaxed">
                    Creas una receta con los ingredientes y cantidades que usas. MiMargen toma el costo de cada insumo de tu inventario, le suma la mano de obra, los gastos fijos que asignes y la merma del proceso. El resultado es el costo real por unidad producida.
                </div>
            </details>
            <!-- FAQ 3 -->
            <details class="group bezel-outer bezel-card reveal reveal-delay-2 faq-open">
                <summary class="flex items-center justify-between cursor-pointer px-6 py-5 text-base font-semibold text-slate-900 hover:text-brand-600 transition-colors duration-350 list-none" style="transition-timing-function: var(--ease-premium)">
                    ¿MiMargen sirve si no hago alimentos?
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-350 shrink-0 ml-4" style="transition-timing-function: var(--ease-premium)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </summary>
                <div class="px-6 pb-5 text-sm text-slate-600 leading-relaxed">
                    Sí. Aunque el concepto de "receta" viene del mundo gastronómico, funciona para cualquier producto que se fabrique combinando materiales: cosméticos, velas, muebles, textiles. Si transformas insumos en un producto terminado, MiMargen te sirve.
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
                    Sí. MiMargen está integrado con el SII de Chile para emitir boletas, facturas y guías de despacho electrónicas directamente desde la plataforma. No necesitas software adicional.
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
                    La merma es la pérdida de materia prima durante el proceso de producción (evaporación, desperdicio, errores). MiMargen la calcula automáticamente para que conozcas el costo real de cada producto y no pierdas plata sin saberlo.
                </div>
            </details>
            <!-- FAQ 7 -->
            <details class="group bezel-outer bezel-card reveal faq-open">
                <summary class="flex items-center justify-between cursor-pointer px-6 py-5 text-base font-semibold text-slate-900 hover:text-brand-600 transition-colors duration-350 list-none" style="transition-timing-function: var(--ease-premium)">
                    ¿MiMargen funciona para negocios fuera de Chile?
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-350 shrink-0 ml-4" style="transition-timing-function: var(--ease-premium)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </summary>
                <div class="px-6 pb-5 text-sm text-slate-600 leading-relaxed">
                    Sí. Aunque la facturación electrónica está optimizada para Chile, el costeo por receta, inventario y gestión de ventas funcionan para cualquier país. Puedes usar MiMargen en cualquier moneda.
                </div>
            </details>
        </div>
    </div>
</section>

<!-- Final CTA -->
<section class="py-32 sm:py-40 lg:py-48 section-dark-cta text-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-white reveal">
                Deja de adivinar. <span class="text-brand-400">Empieza a saber.</span>
            </h2>
            <p class="mt-6 text-lg sm:text-xl text-slate-300 leading-relaxed reveal reveal-delay-1">
                Cada día que pasas sin conocer tu margen real es un día que puedes estar perdiendo plata. Prueba MiMargen gratis durante 14 días y conoce tu ganancia de verdad.
            </p>

            <!-- Stats -->
            <div class="mt-12 grid grid-cols-3 gap-8 reveal reveal-delay-1">
                <div class="text-center">
                    <div class="text-3xl sm:text-4xl font-bold text-brand-400">500+</div>
                    <div class="text-sm text-slate-400 mt-2">Productores activos</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl sm:text-4xl font-bold text-brand-400">34%</div>
                    <div class="text-sm text-slate-400 mt-2">Margen promedio</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl sm:text-4xl font-bold text-brand-400">14 días</div>
                    <div class="text-sm text-slate-400 mt-2">Prueba gratis</div>
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

            <div class="mt-12 flex flex-col sm:flex-row gap-4 justify-center reveal reveal-delay-2">
                <a href="#precios" class="btn-primary-large btn-premium inline-flex items-center justify-center font-semibold rounded-full text-white group">
                    Empezar gratis — sin tarjeta
                    <span class="btn-icon ml-2 inline-flex">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                    </span>
                </a>
                <a href="https://wa.me/<?= htmlspecialchars($contactWhatsApp, ENT_QUOTES, 'UTF-8') ?>" class="btn-premium inline-flex items-center justify-center px-6 py-3 rounded-full border border-slate-600 text-white text-sm font-semibold hover:bg-slate-800">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    Hablar por WhatsApp
                </a>
            </div>
        </div>
    </div>
</section>
</main>

<!-- Footer -->
<footer class="bg-slate-950 text-slate-400">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-10">
            <!-- Brand Column -->
            <div class="md:col-span-5">
                <div class="flex items-center gap-2.5 mb-5">
                    <img src="/assets/logo-icon.svg" alt="MiMargen" class="w-8 h-8">
                    <span class="text-lg font-bold text-white">Mi<span class="text-brand-400">Margen</span></span>
                </div>
                <p class="text-sm text-slate-400 leading-relaxed max-w-sm mb-6">
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
                    <a href="https://youtube.com/@mimargen" class="text-slate-400 hover:text-brand-400 transition-colors" aria-label="YouTube">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                    </a>
                </div>
            </div>

            <!-- Product Column -->
            <div class="md:col-span-3">
                <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-5">Producto</h3>
                <ul class="space-y-3">
                    <li><a href="#producto" class="text-sm hover:text-brand-400 transition-colors">Características</a></li>
                    <li><a href="#precios" class="text-sm hover:text-brand-400 transition-colors">Precios</a></li>
                    <li><a href="#calculadora" class="text-sm hover:text-brand-400 transition-colors">Calculadora gratuita</a></li>
                    <li><a href="#faq" class="text-sm hover:text-brand-400 transition-colors">Preguntas frecuentes</a></li>
                </ul>
            </div>

            <!-- Contact Column -->
            <div class="md:col-span-4">
                <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-5">Contacto</h3>
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

        <div class="border-t border-slate-800 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-xs text-slate-500">&copy; <?= date('Y') ?> MiMargen. Todos los derechos reservados.</p>
            <p class="text-xs text-slate-500">
                Un producto de <a href="https://ottertech.com" target="_blank" rel="noopener" class="text-brand-400 hover:text-brand-300 transition-colors font-medium">OtterTech</a>
            </p>
            <div class="flex items-center gap-6">
                <a href="#" title="Próximamente" class="text-xs text-slate-500 hover:text-slate-400 transition-colors">Términos</a>
                <a href="#" title="Próximamente" class="text-xs text-slate-500 hover:text-slate-400 transition-colors">Privacidad</a>
            </div>
        </div>
    </div>
</footer>

<!-- Mobile menu toggle -->
<script>
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

<!-- IntersectionObserver for .reveal elements -->
<script>
(function() {
    'use strict';
    var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var reveals = document.querySelectorAll('.reveal');
    if (prefersReducedMotion) {
        reveals.forEach(function(el) { el.classList.add('visible'); });
        return;
    }
    if (!('IntersectionObserver' in window)) {
        reveals.forEach(function(el) { el.classList.add('visible'); });
        return;
    }
    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });
    reveals.forEach(function(el) { observer.observe(el); });
})();
</script>

<!-- Header scroll effect -->
<script>
(function() {
    'use strict';
    var header = document.getElementById('site-header');
    if (!header) return;
    var lastScroll = 0;
    window.addEventListener('scroll', function() {
        var currentScroll = window.pageYOffset;
        if (currentScroll > 50) {
            header.classList.add('header-solid');
        } else {
            header.classList.remove('header-solid');
        }
        lastScroll = currentScroll;
    }, { passive: true });
})();
</script>

</body>
</html>
