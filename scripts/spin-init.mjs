import { readFile, writeFile } from "node:fs/promises";
import { resolve } from "node:path";

const cwd = process.cwd();
const args = process.argv.slice(2);

const getFlagValue = (flag, fallback = "") => {
  const index = args.indexOf(flag);
  if (index === -1) {
    return fallback;
  }

  return args[index + 1] ?? fallback;
};

const slugify = (value) =>
  value
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");

const readJson = async (path) => JSON.parse(await readFile(path, "utf8"));

const main = async () => {
  const presetsPath = resolve(cwd, "config/brand-presets.json");
  const configPath = resolve(cwd, "config/site.config.json");
  const presets = await readJson(presetsPath);

  const name = getFlagValue("--name", "Starter Site").trim();
  const slug = getFlagValue("--slug", slugify(name)).trim();
  const tagline = getFlagValue("--tagline", "").trim();
  const preset = getFlagValue("--preset", "editorial-signal").trim();

  if (!presets[preset]) {
    throw new Error(`Unknown brand preset: ${preset}`);
  }

  const siteConfig = {
    site: {
      name,
      slug,
      tagline,
    },
    brand: {
      preset,
    },
  };

  await writeFile(configPath, `${JSON.stringify(siteConfig, null, 2)}\n`, "utf8");

  process.stdout.write(
    [
      `Initialized site config`,
      `- name: ${name}`,
      `- slug: ${slug}`,
      `- preset: ${preset}`,
      `- config: config/site.config.json`,
    ].join("\n") + "\n",
  );
};

main().catch((error) => {
  console.error(error.message);
  process.exitCode = 1;
});
