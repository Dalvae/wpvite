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
