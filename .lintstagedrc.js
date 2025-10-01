const path = require("path");

const buildEslintCommand = (filenames) =>
  `eslint ${filenames.map((f) => path.relative(process.cwd(), f)).join(" ")}`;

const buildPrettierCommand = (filenames) =>
  `prettier --write --ignore-unknown ${filenames
    .map((f) => path.relative(process.cwd(), f))
    .join(" ")}`;

const buildPrettierPhpCommand = (filenames) =>
  `vendor/bin/php-cs-fixer --config=.php-cs-fixer.dist.php fix ${filenames
    .map((f) => path.relative(process.cwd(), f))
    .join(" ")}`;

module.exports = {
  // "src/**/*.{js,jsx,ts,tsx}": [buildEslintCommand],
  "*.{json,js,ts,jsx,tsx,html,css}": [buildPrettierCommand],
  "*.php": [buildPrettierPhpCommand],
};
