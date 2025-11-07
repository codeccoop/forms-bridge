const { defineConfig } = require("eslint/config");
const globals = require("globals");
const js = require("@eslint/js");
const react = require("eslint-plugin-react");
const eslintConfigPrettier = require("eslint-config-prettier/flat");

const languageOptions = {
  parserOptions: {
    ecmaFeatures: {
      jsx: true,
    },
  },
  globals: {
    ...globals.browser,
    wp: "readonly",
  }
};

module.exports = defineConfig([
  {
    ignores: ["vendor/", "node_modules/", "*.config.js", "composer.*", "package*.json"],
  },
  {
    settings: {
      react: {
        version: "18",
      }
    },
    files: ["forms-bridge/src/**/*.js", "forms-bridge/src/**/*.jsx"],
    plugins: { js, react },
    extends: [
      "js/recommended",
      react.configs.flat.recommended,
      react.configs.flat["jsx-runtime"]
    ],
    languageOptions,
    rules: {
      "no-case-declarations": 0,
      "react/prop-types": 0,
      "react/jsx-no-target-blank": 0,
    }
  },
  eslintConfigPrettier,
]);
