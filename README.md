# WordPress ACF Boilerplate

Boilerplate per lo sviluppo di temi WordPress custom con ACF PRO, Vite e Bootstrap 5.

**Requisiti:** PHP 8.2+, Node 18+, [Bun](https://bun.sh), Composer

---

## Installazione

### 1. Clona la repository

```bash
git clone <url-repository>
cd <nome-progetto>
```

### 2. Configura le variabili d'ambiente

```bash
cp .env.example .env
```

Modifica `.env` con i tuoi valori:

| Variabile | Descrizione |
|---|---|
| `LOCAL_DOMAIN` | Dominio locale WordPress (es. `miosito.local`) |
| `THEME_NAME` | Nome della cartella del tema (default: `default-theme`) |
| `ACF_PRO_KEY` | Licenza ACF PRO |

### 3. Configura ACF PRO

Crea il file `auth.json` nella root (è escluso dal repository):

```json
{
  "http-basic": {
    "connect.advancedcustomfields.com": {
      "username": "LA_TUA_LICENZA_ACF_PRO",
      "password": "https://tuosito.com"
    }
  }
}
```

> La `username` è la chiave di licenza ACF PRO, la `password` è l'URL del sito registrato.

### 4. Installa le dipendenze PHP

```bash
composer install
```

### 5. Installa le dipendenze Node

```bash
bun install
```

### 6. Configura WordPress per lo sviluppo locale

Aggiungi questa riga in `wp-config.php` per attivare il dev server di Vite:

```php
define( 'VITE_DEV', true );
```

> Ricordati di rimuoverla (o impostarla a `false`) prima del deploy in produzione.

---

## Sviluppo

Avvia il dev server di Vite con HMR (Hot Module Replacement):

```bash
bun run dev
```

Vite si avvia su `http://localhost:5173` e fa da proxy verso il tuo WordPress locale. Apri il sito tramite il dominio locale configurato in `.env` — non direttamente dalla porta 5173.

---

## Build per produzione

```bash
bun run build
```

Genera i file compilati e hashati in `wp-content/themes/<THEME_NAME>/assets/dist/`. Prima del deploy, assicurati che `VITE_DEV` non sia definito (o sia `false`) in `wp-config.php`.

---

## Linting

```bash
# JavaScript
bun run lint:js

# CSS / SCSS
bun run lint:css

# PHP (richiede composer install)
vendor/bin/phpcs
```

---

## Struttura del tema

```
wp-content/themes/default-theme/
├── assets/
│   ├── src/
│   │   ├── js/          # JavaScript (entry: main.js)
│   │   └── sass/        # SCSS (entry: style.scss)
│   └── dist/            # File compilati (generati, non versionati)
├── config/
│   ├── theme-config.php      # Menu, ACF options, CSP, hooks
│   ├── postType-config.php   # Custom post types
│   ├── taxonomy-config.php   # Custom taxonomies
│   ├── thumbnail-config.php  # Dimensioni immagini
│   └── disableComment.php    # Disabilita i commenti
├── template/
│   ├── tmpl-functions.php    # Helper Vite, render, SVG, Maps
│   ├── blocks/               # Blocchi ACF
│   └── components/           # Componenti riutilizzabili
├── pages/                    # Template di pagina WordPress
├── acf-json/                 # Configurazioni ACF (versionati)
├── functions.php
├── header.php
├── footer.php
└── style.css
```

---

## Content Security Policy

Il boilerplate include un CSP configurabile via filtro WordPress. Per personalizzarlo, aggiungi in `functions.php` o in un plugin:

```php
add_filter( 'theme_csp_directives', function( array $directives ): array {
    $directives['script-src'] .= ' https://mio-dominio-esterno.com';
    return $directives;
} );
```

---

## CI/CD

La repository include una pipeline GitHub Actions (`.github/workflows/ci.yml`) che esegue automaticamente:

- **PHP lint** — PHPCS con WordPress Coding Standards
- **JS/CSS lint** — ESLint + Stylelint
- **Build** — `vite build` con verifica del manifest

Si attiva su push e pull request verso `main` e `develop`.

---

## Plugin inclusi via Composer

| Plugin | Scopo |
|---|---|
| ACF PRO | Custom fields e page builder |
| Yoast SEO | SEO |
| Wordfence | Sicurezza |
| Contact Form 7 | Form di contatto |
| Redirection | Gestione redirect 301 |
| SVG Support | Caricamento SVG in media library |
| Google Tag Manager | GTM integration |
| Classic Editor | Editor classico WordPress |
| Duplicate Page | Duplicazione pagine |
