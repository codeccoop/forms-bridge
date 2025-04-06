// source
import { useGeneral } from "../../providers/Settings";

const {
  PanelBody,
  ToggleControl,
  __experimentalSpacer: Spacer,
} = wp.components;
const { useMemo } = wp.element;
const { __ } = wp.i18n;

export default function Integrations() {
  const [general, patch] = useGeneral();

  const toggle = (integration) =>
    patch({
      ...general,
      integrations: {
        ...general.integrations,
        [integration]: !general.integrations[integration],
      },
    });

  const isEmpty = useMemo(
    () => Object.keys(general.integrations).length === 0,
    [general]
  );
  const isMulti = useMemo(
    () => Object.keys(general.integrations).length > 1,
    [general]
  );
  const isUnconfigured = useMemo(
    () =>
      Object.keys(general.integrations).reduce(
        (isEmpty, key) => isEmpty && !general.integrations[key],
        true
      ),
    [general]
  );

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
              <a href="/wp-admin/plugin-install.php?s=contact%2520form%25207&tab=search&type=term">
                Contact Form 7
              </a>
            </li>
            <li>
              <a href="https://www.gravityforms.com/">GravityForms</a>
            </li>
            <li>
              <a href="/wp-admin/plugin-install.php?s=wpforms%2520lite&tab=search&type=term">
                WPForms Lite
              </a>
            </li>
            <li>
              <a href="/wp-admin/plugin-install.php?s=ninja%2520forms&tab=search&type=term">
                NinjaForms
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
      {Object.entries(general.integrations).map(([integration, enabled]) => {
        return (
          <div
            key={integration}
            style={{ display: "flex", justifyContent: "left", height: "2em" }}
          >
            <ToggleControl
              __nextHasNoMarginBottom
              label={__(integration, "forms-bridge")}
              checked={enabled}
              onChange={() => toggle(integration)}
            />
          </div>
        );
      })}
    </PanelBody>
  );
}
