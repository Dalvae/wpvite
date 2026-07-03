# Section Library

Available reusable section wrappers live in `template-parts/sections/`.

Each wrapper resolves to:

- a scene preset from `inc/section-helpers.php`
- `components/section-scene.php`
- a section body in `components/section-bodies/`

## Implemented Sections

### `hero-split`

Wrapper:

- `template-parts/sections/hero-split.php`

Body:

- `components/section-bodies/hero.php`

Main args:

- `kicker`
- `title`
- `intro`
- `title_tag`
- `actions`
- `media_image_url`
- `media_image_alt`
- `media_html`

Default scene behavior:

- fullscreen
- split hero layout

### `hero-centered`

Wrapper:

- `template-parts/sections/hero-centered.php`

Body:

- `components/section-bodies/hero.php`

Main args:

- `kicker`
- `title`
- `intro`
- `title_tag`
- `actions`

Default scene behavior:

- fullscreen
- centered layout

### `hero-video`

Wrapper:

- `template-parts/sections/hero-video.php`

Body:

- `components/section-bodies/hero-video.php`

Main args:

- `kicker`
- `title`
- `title_accent`
- `intro`
- `actions`
- `video_url` / `video_id`
- `video_fallback_image` / `image_id`
- `mobile_image_id`
- `ticker_items`
- `proximity_panel`

Default scene behavior:

- fullscreen neutral video/still hero
- shell-less section wrapper so the media can fill the viewport
- optional action buttons, ticker labels, and highlight panel

For WPML, every reusable `hero-video` field used in manifests must also be in
`config/section-field-maps.json`; then seed from manifests and regenerate
`wpml-config.xml`. Complex fields such as `actions`, `ticker_items`, and
`proximity_panel` are stored as JSON meta values, so verify a real WPML XLIFF
round-trip before promising per-subfield translator controls.

### `stats-band`

Wrapper:

- `template-parts/sections/stats-band.php`

Body:

- `components/section-bodies/stats-band.php`

Main args:

- `kicker`
- `title`
- `intro`
- `items`

Item contract:

- `value`
- `label`

### `offer-cards`

Wrapper:

- `template-parts/sections/offer-cards.php`

Body:

- `components/section-bodies/offer-cards.php`

Main args:

- `kicker`
- `title`
- `intro`
- `items`

Item contract:

- `title`
- `text`
- `description`
- `meta`
- `subtitle`
- `badge`
- `href`
- `url`
- `action_label`
- `icon`

### `proof-results`

Wrapper:

- `template-parts/sections/proof-results.php`

Body:

- `components/section-bodies/proof-results.php`

Main args:

- `kicker`
- `title`
- `intro`
- `items`

Item contract:

- `title`
- `quote`
- `text`
- `description`
- `author`
- `role`
- `source_label`
- `source`
- `href`

### `faq-accordion`

Wrapper:

- `template-parts/sections/faq-accordion.php`

Body:

- `components/section-bodies/faq-accordion.php`

Main args:

- `kicker`
- `title`
- `intro`
- `items`

Item contract:

- `question`
- `title`
- `answer`
- `content`
- `answer_html`

### `final-cta`

Wrapper:

- `template-parts/sections/final-cta.php`

Body:

- `components/section-bodies/final-cta.php`

Main args:

- `kicker`
- `title`
- `intro`
- `items`

Item contract:

- `kicker`
- `title`
- `text`
- `description`
- `href`
- `url`
- `action_label`
- `variant`

## Contract Design Rule

Keep contracts intentionally small and composable.

Preferred shared keys:

- `kicker`
- `title`
- `intro`
- `items`
- `actions`
- `href`
- `url`
- `text`
- `description`

Avoid one-off keys unless the section truly needs them.

## Next Candidates

- `contact-shell`
- `pricing-packages`
- `structured-list`
- `social-proof`
- `editorial-split`
- `logo-cloud`
