import { mkdir, readFile, writeFile } from "node:fs/promises";
import { resolve } from "node:path";

const cwd = process.cwd();
const args = process.argv.slice(2);
const writeOutput = args.includes("--write");
const jsonOutput = args.includes("--json");
const allowPlanned = args.includes("--allow-planned");
const manifestArg = args.find((arg) => !arg.startsWith("--")) ?? "config/site-composer.example.json";

const readJson = async (path) => {
  const raw = await readFile(path, "utf8");
  return JSON.parse(raw);
};

const ensureImplemented = (registry, key, type) => {
  const entry = registry[key];
  if (!entry) {
    throw new Error(`Unknown ${type}: ${key}`);
  }

  if (entry.status !== "implemented" && !allowPlanned) {
    throw new Error(
      `${type} "${key}" is marked as ${entry.status}. Use --allow-planned to accept non-implemented presets.`,
    );
  }

  return entry;
};

const main = async () => {
  const manifestPath = resolve(cwd, manifestArg);
  const presetsPath = resolve(cwd, "config/site-composer.presets.json");

  const [manifest, presets] = await Promise.all([readJson(manifestPath), readJson(presetsPath)]);

  const site = manifest.site ?? {};
  const brand = manifest.brand ?? {};
  const pageFamilies = Array.isArray(manifest.pageFamilies) ? manifest.pageFamilies : [];
  const addons = Array.isArray(manifest.addons) ? manifest.addons : [];

  if (!site.name || !site.slug) {
    throw new Error("Manifest requires site.name and site.slug.");
  }

  if (!brand.designSystem) {
    throw new Error("Manifest requires brand.designSystem.");
  }

  const designSystem = ensureImplemented(presets.designSystems, brand.designSystem, "design system");
  const resolvedFamilies = pageFamilies.map((page) => {
    if (!page.family) {
      throw new Error("Every pageFamilies entry must define family.");
    }

    const family = ensureImplemented(presets.pageFamilies, page.family, "page family");
    return {
      family: page.family,
      slug: page.slug ?? page.family,
      title: page.title ?? page.family,
      description: family.description,
      status: family.status,
    };
  });

  const resolvedAddons = addons.map((addon) => {
    const addonData = presets.addons[addon];
    if (!addonData) {
      throw new Error(`Unknown addon: ${addon}`);
    }

    if (addonData.status !== "implemented" && !allowPlanned) {
      throw new Error(
        `Addon "${addon}" is marked as ${addonData.status}. Use --allow-planned to accept non-implemented addons.`,
      );
    }

    return {
      key: addon,
      status: addonData.status,
      description: addonData.description,
    };
  });

  const composition = {
    generatedAt: new Date().toISOString(),
    site: {
      name: site.name,
      slug: site.slug,
      tagline: site.tagline ?? "",
    },
    brand: {
      designSystem: brand.designSystem,
      description: designSystem.description,
      primaryCta: brand.primaryCta ?? "",
      voice: brand.voice ?? "",
      defaultAddons: designSystem.defaultAddons ?? [],
    },
    pageFamilies: resolvedFamilies,
    addons: resolvedAddons,
  };

  if (writeOutput) {
    const outputDir = resolve(cwd, "tmp");
    const outputPath = resolve(outputDir, "site-composer-output.json");
    await mkdir(outputDir, { recursive: true });
    await writeFile(outputPath, `${JSON.stringify(composition, null, 2)}\n`, "utf8");
  }

  if (jsonOutput) {
    process.stdout.write(`${JSON.stringify(composition, null, 2)}\n`);
    return;
  }

  const summary = [
    `Site: ${composition.site.name} (${composition.site.slug})`,
    `Design system: ${composition.brand.designSystem}`,
    `Tagline: ${composition.site.tagline || "-"}`,
    `Primary CTA: ${composition.brand.primaryCta || "-"}`,
    `Voice: ${composition.brand.voice || "-"}`,
    "",
    "Page families:",
    ...composition.pageFamilies.map((page) => `- ${page.family} -> /${page.slug}`),
    "",
    "Addons:",
    ...composition.addons.map((addon) => `- ${addon.key} [${addon.status}]`),
  ].join("\n");

  process.stdout.write(`${summary}\n`);
};

main().catch((error) => {
  console.error(error.message);
  process.exitCode = 1;
});
