const path = require("path");

const buildEslintCommand = (filenames) =>
  `eslint ${filenames.map((f) => path.relative(process.cwd(), f)).join(" ")}`;

const buildPrettierCommand = (filenames) =>
  `prettier --write --ignore-unknown ${filenames
    .map((f) => path.relative(process.cwd(), f))
    .join(" ")}`;

const buildPhpCbfCommand = (filenames) =>
  `vendor/bin/phpcbf -n ${filenames
    .map((f) => path.relative(process.cwd(), f))
    .join(" ")}`;

module.exports = {
  "src/**/*.{js,jsx}": [buildEslintCommand],
  "*.{json,js,jsx,html,css}": [buildPrettierCommand],
  "*.php": [buildPhpCbfCommand],
};
