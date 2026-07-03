#!/usr/bin/env node
/**
 * Apply config/site.config.json to local WordPress options using WP-CLI.
 *
 * This keeps the repo config as the single source of truth for site globals
 * while letting WordPress/WPML/admin flows read normal WP options.
 */

import { readFile } from "node:fs/promises";
import { spawnSync } from "node:child_process";
import { resolve } from "node:path";

const cwd = process.cwd();
const args = process.argv.slice(2);

const getFlagValue = (flag, fallback = "") => {
  const index = args.indexOf(flag);
  return index === -1 ? fallback : (args[index + 1] ?? fallback);
};

const hasFlag = (flag) => args.includes(flag);
const wpPath = getFlagValue("--wp-path", process.env.WP_PATH ?? "").trim();
const dryRun = hasFlag("--dry-run");

const readJson = async (path) => JSON.parse(await readFile(path, "utf8"));

const runWp = (wpArgs) => {
  const fullArgs = wpPath ? [`--path=${wpPath}`, ...wpArgs] : wpArgs;
  const printable = ["wp", ...fullArgs].join(" ");

  if (dryRun) {
    process.stdout.write(`DRY ${printable}\n`);
    return;
  }

  const result = spawnSync("wp", fullArgs, { stdio: "inherit" });
  if (result.status !== 0) {
    throw new Error(`Command failed: ${printable}`);
  }
};

const updateOption = (key, value) => {
  if (String(value ?? "").trim() === "") {
    return;
  }
  runWp(["option", "update", key, String(value)]);
};

const main = async () => {
  const config = await readJson(resolve(cwd, "config/site.config.json"));
  const site = config.site ?? {};
  const contact = site.contact ?? {};

  updateOption("blogname", site.name);
  updateOption("blogdescription", site.tagline);
  updateOption("admin_email", contact.email);
  updateOption("starter_site_slug", site.slug);
  updateOption("starter_contact_email", contact.email);
  updateOption("starter_contact_phone", contact.phone);

  process.stdout.write(`${dryRun ? "DRY " : ""}site config applied\n`);
};

main().catch((error) => {
  console.error(error.message);
  process.exitCode = 1;
});
