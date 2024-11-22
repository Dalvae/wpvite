## WordPress Starter TailwindCSS Vite Theme

Clean and lightweight starter theme based on TailwindCSS, AlpineJS, Vite, SCSS, Turbo and incorporates loads of
functions ready to be turned on and off in the functions.php and inc/ directory. Trimed and adapted to docker
from https://github.com/mrl22/WordPress-Starter-TailwindCSS-Vite-Theme

This theme strips WordPress back, removes all the bloat and leaves you with a clean starting point, a favicon, a single
css and js resource. **Just Four** requests in total.

Some features within this theme may not be laid out the way you like to work, but feel free to customise it. I developed
this theme for ourselves and to streamline getting started on a new bespoke theme for a project.

The JS and SCSS within this theme are compiled using Vite from the /src folder to the /dist folder. A basic
understanding of npm and composer is required to use this theme.

## Installation & Run

### Development

```
$ npm install
$ npm run build
```

You can also run `npm run watch` this which will start a Vite server for you to develop in realtime. You must change
`IS_VITE_DEVELOPMENT` in functions.php to true for this to work.

### Useful Files

Files from `src` will be compiled in to `dist` using Vite. If you wish to retain ownership of the source code, do not
upload or give your client access or access to the `src` folder.

- `src/theme.js` - Theme JavaScript File.
- `src/theme.scss` - Theme CSS Styles File.
- `src/assets/` - use this folder for any assets, these will be compiled in to `dist/assets/`.

### Live Server

```
$ composer install
```

Make sure your compiled `build` folder is uploaded.

### Docker Setup

1. Copy `.env.example` to `.env` and adjust variables if needed:

   ```bash
   cp .env.example .env
   ```

2. Start Docker containers:

   ```bash
   docker-compose up -d
   ```

3. Access:

   - WordPress: http://localhost:80
   - phpMyAdmin: http://localhost:8080

4. Theme development:
   ```bash
   npm install
   npm run watch   # For development
   npm run build   # For production
   ```

Note: If using Apple Silicon, uncomment the `platform: linux/x86_64` line in docker-compose.yml

```
## Packages

- Vite
- TailwindCSS
- PostCSS
- AlpineJS
- Turbo (Loaded via NPM)
- Favicon - https://realfavicongenerator.net/

## Theme Functions / Features

I recommend that you read the entire functions.php and src/ folder. Uncomment / comment out features which you
need in your theme.
```
