import { execSync } from "child_process";
import { existsSync, copyFileSync } from "fs";

const run = (cmd) => {
  console.log(`→ ${cmd}`);
  execSync(cmd, { stdio: "inherit" });
};

console.log("\n⚙ WPVite Setup\n");

// 1. .env
if (!existsSync(".env")) {
  copyFileSync("env.example", ".env");
  console.log("→ Created .env from env.example");
} else {
  console.log("→ .env already exists, skipping");
}

// 2. Dependencies
run("pnpm install");
run("composer install --no-interaction");

// 3. Build assets
run("pnpm build");

// 4. Docker
run("docker compose up -d");

// 5. Wait for WP to be ready
console.log("→ Waiting for WordPress...");
let ready = false;
for (let i = 0; i < 30; i++) {
  try {
    execSync("curl -sf http://localhost:8000/ > /dev/null 2>&1");
    ready = true;
    break;
  } catch {
    execSync("sleep 2");
  }
}

if (ready) {
  console.log("→ WordPress is up");

  // 6. Activate theme
  try {
    run('docker compose --profile cli run --rm wpcli wp theme activate "${THEME_SLUG:-starter}"');
  } catch {
    console.log("→ Could not activate theme (WP install might not be complete yet)");
    console.log("  Complete WP setup at http://localhost:8000 then run:");
    console.log('  pnpm wp theme activate starter');
  }
} else {
  console.log("→ WordPress didn't start in time. Check: docker compose logs wp");
}

console.log("\n✓ Done. Run 'pnpm dev' to start developing.\n");
