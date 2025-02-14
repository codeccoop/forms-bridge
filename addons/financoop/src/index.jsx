// source
import Addon from "./Addon";

const { createRoot } = wp.element;
const { __ } = wp.i18n;

wpfb.join("addons", ({ data: registry }) => {
  registry["financoop"] = __("financoop", "forms-bridge");

  const root = document.createElement("div");
  root.style.visibility = "hidden";
  document.body.appendChild(root);

  createRoot(root).render(<Addon />);
});
