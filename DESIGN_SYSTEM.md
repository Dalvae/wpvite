# Design System Base (WPVite)

Base inicial para estandarizar futuros themes WordPress con una sola fuente de verdad.

## 1) Tokens semánticos

- CSS source of truth: `src/css/design-system.css`
- Machine-readable source of truth: `src/tokens/design-tokens.json`

Convenciones:

- Nombres semánticos (`primary`, `warning`, `danger`, `surface`, `foreground`), no nombres por color literal (`blue500`).
- Tokens globales prefijados con `--ds-` para evitar choques.
- Mapping a Tailwind v4 vía `@theme` para usar utilidades tipo `bg-primary`, `text-foreground`, `rounded-button-md`.

## 2) Radius en un solo lugar

Definidos en `:root`:

- `--ds-radius-button-sm`
- `--ds-radius-button-md`
- `--ds-radius-button-lg`
- `--ds-radius-pill`
- `--ds-radius-card`

El componente PHP de botón usa estas variantes por contrato:

- `variant`: `primary | secondary | tertiary | warning | success | danger | ghost`
- `size`: `sm | md | lg`
- `radius`: `sm | md | lg | pill`

## 3) Tipografía: Display + Sans + escala fluida

Pairing elegido:

- Display: `Fraunces`
- Sans: `Manrope`

Escala con `clamp()` y progresión inspirada en Fibonacci/razón áurea (suavizada para UI).

Idea:

- Fibonacci converge hacia `phi` (~1.618), pero ese salto suele ser agresivo para interfaces.
- Se usa una progresión más controlada con pasos fluidos por viewport para mantener jerarquía sin romper layout.

Roles:

- Body: `--ds-step-0`
- H1: `--ds-step-5`
- H2: `--ds-step-4`
- H3: `--ds-step-3`
- H4: `--ds-step-2`
- H5: `--ds-step-1`
- H6: `--ds-step-0`

## 4) Design system para LLMs

Para que IA/código automático lo use bien:

- Mantener tokens en JSON plano con estructura consistente (DTCG style: `$type`, `$value`).
- No duplicar semánticas (evitar `brandPrimary`, `mainPrimary`, `primaryColor` para el mismo rol).
- Mantener aliases explícitos (ej. `roles.h1 -> fontSize.step5`).
- Versionar cambios (`meta.version`) y romper contrato solo en major.

Ejemplo de instrucción para agentes:

```txt
Usa únicamente tokens semánticos desde src/tokens/design-tokens.json.
No inventes colores/radius/fuentes fuera de los tokens existentes.
Si falta un token, proponlo como cambio al design system.
```

## 5) Uso rápido en templates

```php
<?php get_template_part('components/button', null, [
  'text' => 'Cotizar',
  'href' => '/contacto',
  'variant' => 'primary',
  'size' => 'md',
  'radius' => 'pill',
]); ?>
```
