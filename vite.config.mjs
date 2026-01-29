import { defineConfig } from "vite";
import tailwindcss from "@tailwindcss/vite";
import liveReload from "vite-plugin-live-reload";
import { v4wp } from "@kucrut/vite-for-wp";
import { dirname } from "path";
import { fileURLToPath } from "url";

const __dirname = dirname(fileURLToPath(import.meta.url));

export default defineConfig({
  plugins: [
    tailwindcss(),
    v4wp({
      input: "src/theme.js",
      outDir: "dist",
    }),
    liveReload(__dirname + "/**/*.php"),
  ],
});
