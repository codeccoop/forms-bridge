const globals = require("globals");
const pluginJs = require("@eslint/js");
const prettier = require("prettier");

module.exports = [
  {
    languageOptions: { globals: globals.browser },
    ignores: [
      "*.config.js",
      ".lintstagedrc.js",
      ".prettierrc",
      ".prettierrc-php",
      "package*.json",
    ],
  },
  pluginJs.configs.recommended,
  prettier,
];
