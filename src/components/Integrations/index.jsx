// source
import { useIntegrations } from "../../hooks/useGeneral";

const {
  PanelBody,
  ToggleControl,
  __experimentalSpacer: Spacer,
} = wp.components;
const { useMemo, useCallback } = wp.element;
const { __ } = wp.i18n;

export default function Integrations() {
  const [integrations, setIntegrations] = useIntegrations();

  const toggleEnabled = useCallback(
    (target) => {
      const newIntegrations = integrations.map(({ name, enabled }) => {
        if (target === name) {
          enabled = !enabled;
        }

        return { name, enabled };
      });

      window.__wpfbInvalidated = true;
      setIntegrations(newIntegrations);
    },
    [integrations]
  );

  const isEmpty = useMemo(() => integrations.length === 0, [integrations]);
  const isMulti = useMemo(() => integrations.length > 1, [integrations]);
  const isUnconfigured = useMemo(
    () =>
      integrations.reduce((isEmpty, { enabled }) => isEmpty && !enabled, true),
    [integrations]
  );

  const adminUrl =
    (window.location.pathname.match(/(.*)(?=\/wp-admin\/)/, "") || [])[0] || "";

  if (!(isMulti || isEmpty)) return;

  return (
    <PanelBody
      title={__("Integrations", "forms-bridge")}
      initialOpen={isEmpty || isUnconfigured}
    >
      {(isEmpty && (
        <>
          <p>
            {__(
              "It seems you have no available integrations. If you want to use Forms Bridge, you should install, at least one of the following plugins before you can create form bridges.",
              "forms-bridge"
            )}
          </p>
          <ul>
            <li>
              <a
                href={`${adminUrl}/wp-admin/plugin-install.php?s=contact%2520form%25207&tab=search&type=term`}
              >
                Contact Form 7
              </a>
            </li>
            <li>
              <a href="https://www.gravityforms.com/" target="_blank">
                GravityForms
              </a>
            </li>
            <li>
              <a href="https://wpforms.com/" target="_blank">
                WPForms
              </a>
            </li>
            <li>
              <a
                href={`${adminUrl}/wp-admin/plugin-install.php?s=ninja%2520forms&tab=search&type=term`}
              >
                NinjaForms
              </a>
            </li>
            <li>
              <a
                href={`${adminUrl}/wp-admin/plugin-install.php?s=woocommerce&tab=search&type=term`}
              >
                WooCommerce
              </a>
            </li>
          </ul>
        </>
      )) || (
        <p>
          {__(
            "Select which plugins you want to integrate with Forms Bridge",
            "forms-bridge"
          )}
        </p>
      )}
      <Spacer paddingBottom="5px" />
      {integrations.map(({ name, title, enabled }) => {
        return (
          <div
            key={name}
            style={{ display: "flex", justifyContent: "left", height: "2em" }}
          >
            <ToggleControl
              __nextHasNoMarginBottom
              label={title}
              checked={enabled}
              onChange={() => toggleEnabled(name)}
            />
          </div>
        );
      })}
    </PanelBody>
  );
}
