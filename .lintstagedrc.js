const path = require("path");

const buildEslintCommand = (filenames) =>
  `eslint ${filenames
    .map((f) => path.relative(process.cwd(), f))
    .join(" --file ")}`;

const buildPrettierCommand = (filenames) =>
  `prettier --write --ignore-unknown ${filenames
    .map((f) => path.relative(process.cwd(), f))
    .join(" ")}`;

const buildPrettierPhpCommand = (filenames) =>
  `prettier --write --ignore-unknown --config .prettierrc-php ${filenames
    .map((f) => path.relative(process.cwd(), f))
    .join(" ")}`;

module.exports = {
  "src/**/*.{js,jsx,ts,tsx}": [buildEslintCommand],
  "*.{json,js,ts,jsx,tsx,html,css}": [buildPrettierCommand],
  "*.php": [buildPrettierPhpCommand],
};
