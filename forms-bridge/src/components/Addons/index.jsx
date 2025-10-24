// source
import { useAddons } from "../../hooks/useGeneral";

const { useMemo, useCallback } = wp.element;
const { PanelBody, __experimentalSpacer: Spacer } = wp.components;
const { __ } = wp.i18n;

export default function Addons() {
  const [addons, setAddons] = useAddons();

  const toggleEnabled = useCallback(
    (target) => {
      const newAddons = addons.map(({ name, enabled }) => {
        if (name === target) {
          enabled = !enabled;
        }

        return { name, enabled };
      });

      window.__wpfbInvalidated = true;
      window.__wpfbReload = true;
      setAddons(newAddons);
    },
    [addons]
  );

  const sortedAddons = useMemo(
    () => addons.sort((a, b) => (a.name > b.name ? 1 : -1)),
    [addons]
  );

  return (
    <PanelBody title={__("Addons", "forms-bridge")} initialOpen={false}>
      <p>
        {__(
          "Each addon allows you to create API specific bridges and comes with a library of bridge templates and workflow jobs",
          "forms-bridge"
        )}
      </p>
      <Spacer paddingBottom="5px" />
      <div style={{ display: "flex", flexWrap: "wrap", gap: "20px" }}>
        {sortedAddons.map(({ name, title, enabled, logo }) => (
          <button
            key={name}
            tabIndex={0}
            style={{
              width: "200px",
              height: "180px",
              borderRadius: "5px",
              display: "flex",
              flexDirection: "column",
              justifyContent: "center",
              alignItems: "center",
              cursor: "pointer",
              padding: "20px",
              color: enabled
                ? "var(--wp-components-color-accent,var(--wp-admin-theme-color,#3858e9))"
                : "inherit",
              border: enabled ? "2px solid" : "none",
            }}
            onClick={() => toggleEnabled(name)}
          >
            <div style={{ flex: 1, display: "flex", alignItems: "center" }}>
              <img
                alt={name}
                src={logo}
                width="100px"
                height="50px"
                style={{
                  marginTop: "-8px",
                  objectFit: "contain",
                  objectPosition: "center",
                  marginLeft: "5px",
                }}
              />
            </div>
            <h4 style={{ margin: 0, fontSize: "1rem" }}>{title}</h4>
          </button>
        ))}
      </div>
      <Spacer paddingY="calc(10px)" />
    </PanelBody>
  );
}
