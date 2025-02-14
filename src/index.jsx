// source
import SettingsPage from "./SettingsPage";
import ErrorBoundary from "./ErrorBoundary";

const domReady = wp.domReady;
const { createRoot } = wp.element;

domReady(() => {
  const root = createRoot(document.getElementById("forms-bridge"));
  const addons = wpfb.bus("addons", {});
  root.render(
    <div style={{ position: "relative" }}>
      <ErrorBoundary fallback={<h1>Error</h1>}>
        <SettingsPage addons={addons} />
      </ErrorBoundary>
    </div>
  );
});
