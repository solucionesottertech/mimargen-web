# MiMargen Landing — Nota para agentes

## ⚠️ Lee esto antes de editar el landing

Este repositorio **NO es la fuente de verdad del landing**. Es el **build standalone de
despliegue** para el dominio apex `mimargen.cl`.

El landing se desarrolla realmente dentro del repo **OtterErp**:

```
c:\wamp64\www\ottererp
```

Cuando se hacen cambios de diseño/contenido al landing, se hacen en OtterErp y luego se
**portan** a este repo. Si te piden "actualizar el landing", normalmente significa traer
los cambios desde OtterErp hacia acá.

## Mapeo de archivos (OtterErp → este repo)

| OtterErp (fuente)                  | Este repo (deploy)        |
|------------------------------------|---------------------------|
| `public/landing.php`               | `landing.php`             |
| `dist/landing.css` (build Tailwind)| `assets/landing.css`      |
| `assets/js/landing.js`             | `assets/landing.js`       |
| `assets/calculator.js`             | `assets/calculator.js`    |

> El CSS se compila con Tailwind (`tailwind.landing.config.js`) en OtterErp; aquí solo
> vive el artefacto compilado. Las fuentes (Geist) se cargan desde Google Fonts en el
> `<head>`, no desde `assets/fonts/` (esa carpeta es legado del build anterior con Astro).

## Diferencia clave: el backend NO es el mismo

`public/landing.php` de OtterErp usa el framework (`App\Core\PlatformSettings`,
`App\Core\Storage\SqliteStorage`, `declare(strict_types=1)`, `\App\Core\Assets::css()`).

Este repo es **standalone**: usa `core/bootstrap.php` + `core/JsonStorage.php` +
`core/Encryption.php`. **No** dependas de las clases `App\Core\*` aquí.

Al portar `landing.php`, se conserva el **cuerpo HTML** de OtterErp (desde `<!doctype html>`)
pero se mantiene la **cabecera PHP standalone** de este repo. Transformaciones necesarias
en el cuerpo:

- `<?= \App\Core\Assets::css('landing.css') ?>` → `<link rel="stylesheet" href="/assets/landing.css">`
- `/assets/js/landing.js` → `/assets/landing.js`
- Persistencia de leads: `SqliteStorage` → `JsonStorage` (en la cabecera PHP)

Variables PHP que el cuerpo espera (definidas en la cabecera): `$appName` (= `MiMargen`),
`$baseDomain` (= `mimargen.cl`), `$heroTitleHtml` (soporta marcador `*palabra*` para el
subrayado de marca), `$formValues[]`, `$ogImage`, `$scheme`, `$canonical`, `$brandLogo`,
contacto/social y `$jsonLdString`. La cabecera también responde JSON si la petición POST
es AJAX (`X-Requested-With: XMLHttpRequest`), usada por el modal "Solicita tu prueba".

## Verificación tras portar

```bash
php -l landing.php                              # sin errores de sintaxis
grep -nE "App\\\\Core|SqliteStorage|Assets::" landing.php   # solo debe aparecer en comentarios
```

Y confirma que todo `/assets/...` referenciado en `landing.php` existe en `assets/`.

## Más contexto

- `DEPLOY.md` — contrato de integración con el backend de OtterErp y pasos de despliegue.
- `core/` — backend standalone (settings, storage JSON cifrado, bootstrap).
