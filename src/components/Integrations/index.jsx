// vendor
import React from "react";
import { PanelBody, PanelRow, ToggleControl } from "@wordpress/components";
import { useMemo } from "@wordpress/element";

// source
import { useGeneral } from "../../providers/Settings";

export default function Integrations() {
  const __ = wp.i18n.__;

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
      {isEmpty && (
        <>
          <p>
            {__(
              "It seems you have no available integrations. If you want to use Forms Bridge, you should install one of the following plugins before you go.",
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
      )}
      {isUnconfigured && (
        <p>
          {__(
            "👋 Welcome! It seems you have more than one forms builder plugin installed. Before you continue, please, select which plugins do you want to integrate with Forms Bridge.",
            "forms-bridge"
          )}
        </p>
      )}
      {Object.entries(general.integrations).map(([integration, enabled]) => {
        return (
          <PanelRow key={integration}>
            <ToggleControl
              __nextHasNoMarginBottom
              label={__(integration, "forms-bridge")}
              checked={enabled}
              onChange={() => toggle(integration)}
            />
          </PanelRow>
        );
      })}
    </PanelBody>
  );
}
