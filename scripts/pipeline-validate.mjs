import { readdir, readFile } from "node:fs/promises";
import { resolve } from "node:path";

const cwd = process.cwd();

const readJson = async (path) => JSON.parse(await readFile(path, "utf8"));

const main = async () => {
  const siteConfigPath = resolve(cwd, "config/site.config.json");
  const brandPresetsPath = resolve(cwd, "config/brand-presets.json");
  const sectionPresetsPath = resolve(cwd, "config/site-composer.presets.json");
  const fieldMapsPath = resolve(cwd, "config/section-field-maps.json");
  const manifestsDir = resolve(cwd, "manifests/pages");

  const [siteConfig, brandPresets, sectionPresets, fieldMapsConfig] = await Promise.all([
    readJson(siteConfigPath),
    readJson(brandPresetsPath),
    readJson(sectionPresetsPath),
    readJson(fieldMapsPath),
  ]);

  const preset = siteConfig?.brand?.preset;
  if (!preset || !brandPresets[preset]) {
    throw new Error(`site.config.json references an unknown brand preset: ${preset}`);
  }

  if (!siteConfig?.site?.name || !siteConfig?.site?.slug) {
    throw new Error("site.config.json requires site.name and site.slug");
  }

  const contact = siteConfig?.site?.contact ?? {};
  if (!contact.email || !contact.phone) {
    throw new Error("site.config.json requires site.contact.email and site.contact.phone");
  }

  const files = (await readdir(manifestsDir)).filter((file) => file.endsWith(".json"));
  const errors = [];
  const manifestSlugs = new Set();

  for (const file of files) {
    const manifest = await readJson(resolve(manifestsDir, file));
    if (manifest.slug) {
      manifestSlugs.add(manifest.slug);
    }
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

    const fieldMap = fieldMapsConfig?.pages?.[manifest.slug];
    if (fieldMap) {
      if (fieldMap.length !== sections.length) {
        errors.push(
          `${file}: section-field-maps has ${fieldMap.length} sections but manifest has ${sections.length}`,
        );
      }
      fieldMap.forEach((mappedSection, index) => {
        const manifestType = sections[index]?.type ?? sections[index]?.section ?? "";
        if (manifestType && mappedSection?.type !== manifestType) {
          errors.push(
            `${file}: section[${index}] type mismatch between field map "${mappedSection?.type}" and manifest "${manifestType}"`,
          );
        }
      });
    }
  }

  Object.keys(fieldMapsConfig?.pages ?? {}).forEach((slug) => {
    if (!manifestSlugs.has(slug)) {
      errors.push(`section-field-maps references "${slug}" but manifests/pages/${slug}.json is missing`);
    }
  });

  if (errors.length) {
    throw new Error(`Pipeline validation failed:\n- ${errors.join("\n- ")}`);
  }

  process.stdout.write(
    [
      `Pipeline validation passed`,
      `- active preset: ${preset}`,
      `- site: ${siteConfig.site.name} (${siteConfig.site.slug})`,
      `- page manifests: ${files.length}`,
    ].join("\n") + "\n",
  );
};

main().catch((error) => {
  console.error(error.message);
  process.exitCode = 1;
});
