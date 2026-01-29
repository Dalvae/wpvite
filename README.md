# WPVite Starter

WordPress starter theme — Vite 7, Tailwind CSS 4, AlpineJS, Turbo. Docker dev environment with WP-CLI.

## Quick Start

```bash
pnpm setup
```

This runs `setup.js` which: copies `.env.example` to `.env`, installs deps (pnpm + composer), builds assets, starts Docker, and installs WordPress.

**Default login:** `admin` / `admin` at `http://localhost:8000/wp-admin`

## Manual Setup

```bash
cp env.example .env          # edit THEME_SLUG, PORT, etc.
pnpm install
composer install
pnpm build
docker compose up -d
pnpm dev                     # start Vite dev server (HMR)
```

## Commands

| Command | Description |
|---------|-------------|
| `pnpm dev` | Start Vite dev server with HMR |
| `pnpm build` | Build production assets to `dist/` |
| `pnpm wp <command>` | Run WP-CLI (e.g. `pnpm wp theme list`) |
| `pnpm setup` | Full setup from scratch |

## Stack

- **Vite 7** — build tool + HMR via [@kucrut/vite-for-wp](https://github.com/kucrut/vite-for-wp)
- **Tailwind CSS 4** — CSS-first config, no `tailwind.config.js` needed
- **AlpineJS** — lightweight DOM interactivity
- **Turbo** — SPA-like navigation
- **Docker** — WordPress + MariaDB + WP-CLI

## Project Structure

```
├── src/
│   ├── theme.js          # JS entry point
│   ├── theme.css         # CSS entry point (Tailwind)
│   ├── css/              # Component styles
│   └── js/               # Component scripts
├── inc/
│   ├── vite.php          # Vite asset loading (via vite-for-wp)
│   ├── performance.php   # WP performance optimizations
│   ├── cleanup.php       # Remove WP bloat
│   ├── general.php       # Theme utilities
│   ├── nav_walker.php    # Tailwind nav walker
│   ├── svg.php           # Sanitized SVG uploads (admin only)
│   └── ...               # ACF, blog, post types, etc.
├── template-parts/       # Reusable template parts
├── components/           # PHP components
├── dist/                 # Built assets (committed)
├── docker-compose.yml    # Docker services
├── vite.config.mjs       # Vite configuration
└── functions.php         # Theme bootstrap
```

## Configuration

Edit `.env` for your project:

```env
THEME_SLUG=starter        # Docker volume mount name
PORT=8000                  # WordPress port
WORDPRESS_DEBUG=1          # WP_DEBUG flag
DB_NAME=wordpress
DB_ROOT_PASSWORD=password
```

## Vite + WordPress Integration

Asset loading is handled by `vite-for-wp`. No manual dev/prod switching needed — it auto-detects whether the Vite dev server is running:

- **Dev:** loads assets from Vite dev server with HMR
- **Prod:** loads hashed assets from `dist/manifest.json`

## Debug Helpers

When `WP_DEBUG` is enabled (default in dev), the theme shows:

- Current template filename (bottom-left corner)
- Tailwind breakpoint indicator (bottom-right corner)

Both are hidden in production.
