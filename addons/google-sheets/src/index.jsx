// vendor
import { createRoot } from "@wordpress/element";

// source
import Addon from "./Addon";

wpfb.join("addons", ({ data: registry }) => {
  const __ = wp.i18n.__;
  registry["google-sheets-api"] = __("Google Sheets", "forms-bridge");

  const root = document.createElement("div");
  root.style.visibility = "hidden";
  document.body.appendChild(root);

  createRoot(root).render(<Addon />);
});
