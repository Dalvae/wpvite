import { readdir, readFile } from "node:fs/promises";
import { resolve } from "node:path";

const cwd = process.cwd();

const readJson = async (path) => JSON.parse(await readFile(path, "utf8"));

const main = async () => {
  const siteConfigPath = resolve(cwd, "config/site.config.json");
  const brandPresetsPath = resolve(cwd, "config/brand-presets.json");
  const sectionPresetsPath = resolve(cwd, "config/site-composer.presets.json");
  const manifestsDir = resolve(cwd, "manifests/pages");

  const [siteConfig, brandPresets, sectionPresets] = await Promise.all([
    readJson(siteConfigPath),
    readJson(brandPresetsPath),
    readJson(sectionPresetsPath),
  ]);

  const preset = siteConfig?.brand?.preset;
  if (!preset || !brandPresets[preset]) {
    throw new Error(`site.config.json references an unknown brand preset: ${preset}`);
  }

  const files = (await readdir(manifestsDir)).filter((file) => file.endsWith(".json"));
  const errors = [];

  for (const file of files) {
    const manifest = await readJson(resolve(manifestsDir, file));
    if (!manifest.slug || !manifest.title) {
      errors.push(`${file}: requires slug and title`);
    }

    const sections = Array.isArray(manifest.sections) ? manifest.sections : [];
    sections.forEach((section, index) => {
      const type = section?.type ?? section?.section ?? "";
      if (!type) {
        errors.push(`${file}: section[${index}] requires type`);
        return;
      }

      if (!sectionPresets.addons?.[type]) {
        errors.push(`${file}: section[${index}] references unknown type "${type}"`);
      }
    });
  }

  if (errors.length) {
    throw new Error(`Pipeline validation failed:\n- ${errors.join("\n- ")}`);
  }

  process.stdout.write(
    [
      `Pipeline validation passed`,
      `- active preset: ${preset}`,
      `- page manifests: ${files.length}`,
    ].join("\n") + "\n",
  );
};

main().catch((error) => {
  console.error(error.message);
  process.exitCode = 1;
});
