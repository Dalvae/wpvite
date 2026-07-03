# VPS Deployment Model

This starter is intended to run and deploy from the VPS itself. Do not add a
public WordPress endpoint that can execute commands or install arbitrary files.

## Recommended exposure

- Public: only the site through Cloudflare Tunnel/Proxy on HTTPS.
- Private/local: WordPress CLI, database, Docker services, build scripts.
- Optional admin URLs: protect with Cloudflare Access per site/admin route.

Use a different Cloudflare hostname per site, for example:

- `site-a.example.com` → local WordPress port/path for site A
- `site-b.example.com` → local WordPress port/path for site B
- `site-c.example.com` → local WordPress port/path for site C
- `site-d.example.com` → local WordPress port/path for site D

## Hard security rules

- Do not expose MariaDB/PostgreSQL to the public internet.
- For multiple sites on the same VPS, give each local database a unique
  `DB_PORT` and keep `DB_IP=127.0.0.1`, or remove DB port publishing entirely
  when external local access is not needed.
- Do not expose Docker socket, WP-CLI, Vite dev server, phpMyAdmin, or shell endpoints.
- Do not use default DB passwords on live sites.
- Do not keep `WORDPRESS_DEBUG=1` in production.
- Do not store `.env.live` secrets in git.
- Do not log authorization headers, cookies, application passwords, or signed URLs.

## Deployment path

For a new spin-off on the VPS:

```bash
pnpm env:generate -- --slug site-a --port 8091 --db-port 33307 --admin-email admin+site-a@example.com
pnpm install --frozen-lockfile
docker compose up -d
pnpm wp:install:local
pnpm site:apply
```

This generates `.env` with random DB password, WordPress salts, and a non-default
admin user/password. The file is written with mode `600` and must not be
committed.

Cloudflare named tunnel routing should point each fixed hostname at the local
loopback port, for example:

```yaml
ingress:
  - hostname: site-a.example.com
    service: http://localhost:8091
  - service: http_status:404
```

Create DNS/CNAME separately through Cloudflare or Azure DNS. Quick Tunnel
`trycloudflare.com` URLs are only temporary smoke-test links and should not be
treated as stable deploy URLs.

Build and deploy locally on the VPS:

```bash
pnpm install --frozen-lockfile
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
pnpm wpml:check
THEME_SLUG=starter pnpm zip:theme
THEME_SLUG=starter WP_PATH=/var/www/site-a/current pnpm deploy:local
```

`scripts/deploy-theme-local.sh` deploys a built ZIP to the local WordPress
theme directory, keeps a timestamped backup, and can activate/flush cache with
WP-CLI. It does not need a WordPress admin password.

The theme zip intentionally includes `vendor/` because `functions.php` requires
`vendor/autoload.php` and Carbon Fields boots from Composer dependencies. If
`vendor/` is missing or incomplete, activation can fatal-error the site. The zip
builder runs `composer install --no-dev --prefer-dist --optimize-autoloader
--no-interaction` automatically when Composer is available; otherwise it fails
with the exact command to run. Do not upload a theme zip without
`vendor/autoload.php`, `vendor/htmlburger/carbon-fields`, and
`vendor/kucrut/vite-for-wp`.

For live/staging automation, prefer fixed WP-CLI commands running locally on
the VPS. Do not use browser automation or public endpoints for actions that
WP-CLI can perform directly.

## Cloudflare Tunnel / Access notes

Cloudflare can be used as the secure front door, but it should forward only to
the intended local HTTP service. It should not forward DB ports, Docker, SSH,
WP-CLI, or arbitrary internal ports.

Recommended controls:

- one hostname per site;
- Cloudflare Access for admin/staging URLs;
- no public direct origin IP if possible;
- origin service bound to `127.0.0.1` or private Docker network;
- separate secrets per site/environment.

## Playwright policy

Prefer local WP-CLI for theme installation, activation, cache flushing, and
content tasks. Use Playwright only for WPML/admin screens that do not have a
stable CLI/REST path, mainly during staging/live translation work.

Never expose an HTTP endpoint that runs Playwright from a request.

Do not use Playwright or custom seed scripts to create Spanish pages. Spanish
content should come from the WPML translation workflow.

Forms are handled with Fluent Forms. Translation of form UI and notification
emails belongs to WPML/string-translation workflows, not duplicated forms or
custom language-specific seed scripts.
