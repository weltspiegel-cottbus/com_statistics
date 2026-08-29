#!/usr/bin/env node

/**
 * Package script for com_statistics
 * Creates a ZIP file ready for Joomla installation
 */

import fs from "fs";
import path from "path";
import { execSync } from "child_process";
import { fileURLToPath } from "url";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const rootDir = path.resolve(__dirname, "..");

// Read version from package.json
const packageJson = JSON.parse(fs.readFileSync(path.join(rootDir, "package.json"), "utf8"));
const componentName = "com_statistics";
const version = packageJson.version;
const outputFile = `${componentName}-${version}.zip`;

// Files and folders to exclude from the package
const excludePatterns = [
    ".idea",
    ".git",
    ".gitignore",
    ".github",
    ".build",
    ".changelogrc",
    ".env",
    "node_modules",
    "package.json",
    "package-lock.json",
    "update-manifest.xml",
    "CHANGELOG.md",
    "README.md",
    "*.zip",
];

// Build the exclude arguments for zip command
const excludeArgs = excludePatterns
    .map((pattern) => `-x "*/${pattern}/*" "*${pattern}*"`)
    .join(" ");

console.log(`Packaging ${componentName} v${version}...\n`);

try {
    // Change to root directory
    process.chdir(rootDir);

    // Remove old zip file if it exists
    if (fs.existsSync(outputFile)) {
        fs.unlinkSync(outputFile);
        console.log(`Removed old ${outputFile}`);
    }

    // Create the zip file
    const zipCommand = `zip -r ${outputFile} . ${excludeArgs}`;
    console.log("Creating ZIP archive...");
    execSync(zipCommand, { stdio: "inherit" });

    console.log(`\n✓ Package created: ${outputFile}`);
    console.log(`✓ Ready for Joomla installation`);
} catch (error) {
    console.error("✗ Error creating package:", error.message);
    process.exit(1);
}
