#!/usr/bin/env node
/**
 * Generate a local .env with per-site random secrets.
 *
 * This is for VPS/local spin-offs. It intentionally does not print secrets.
 */

import { existsSync, writeFileSync, chmodSync } from "node:fs";
import { randomInt } from "node:crypto";

const args = process.argv.slice(2);
const getFlag = (flag, fallback = "") => {
  const index = args.indexOf(flag);
  return index === -1 ? fallback : (args[index + 1] ?? fallback);
};
const hasFlag = (flag) => args.includes(flag);

const output = getFlag("--output", ".env");
const overwrite = hasFlag("--force");

if (existsSync(output) && !overwrite) {
  console.error(`${output} already exists. Use --force to replace it.`);
  process.exit(1);
}

const alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_";
const passwordAlphabet = `${alphabet}!@#%+=`;
const token = (length = 48, chars = alphabet) =>
  Array.from({ length }, () => chars[randomInt(chars.length)]).join("");

const slug = getFlag("--slug", "starter");
const port = getFlag("--port", "8000");
const dbPort = getFlag("--db-port", "3306");
const adminSuffix = token(8, "abcdefghijklmnopqrstuvwxyz0123456789");
const adminUser = getFlag("--admin-user", `siteadmin_${adminSuffix}`);
const adminEmail = getFlag("--admin-email", `admin+${adminSuffix}@example.com`);

const content = `COMPOSE_PROJECT_NAME=${slug}
IP=127.0.0.1
PORT=${port}
DB_IP=127.0.0.1
DB_PORT=${dbPort}
DB_NAME=${slug.replace(/[^a-zA-Z0-9_]/g, "_")}
DB_ROOT_PASSWORD=${token(48, passwordAlphabet)}
THEME_SLUG=${slug}
WORDPRESS_DEBUG=0
WORDPRESS_AUTH_KEY=${token(64, passwordAlphabet)}
WORDPRESS_SECURE_AUTH_KEY=${token(64, passwordAlphabet)}
WORDPRESS_LOGGED_IN_KEY=${token(64, passwordAlphabet)}
WORDPRESS_NONCE_KEY=${token(64, passwordAlphabet)}
WORDPRESS_AUTH_SALT=${token(64, passwordAlphabet)}
WORDPRESS_SECURE_AUTH_SALT=${token(64, passwordAlphabet)}
WORDPRESS_LOGGED_IN_SALT=${token(64, passwordAlphabet)}
WORDPRESS_NONCE_SALT=${token(64, passwordAlphabet)}
WP_ADMIN_USER=${adminUser}
WP_ADMIN_PASSWORD=${token(40, passwordAlphabet)}
WP_ADMIN_EMAIL=${adminEmail}
`;

writeFileSync(output, content, { encoding: "utf8", mode: 0o600 });
chmodSync(output, 0o600);
console.log(`Generated ${output} with random secrets (mode 600).`);
