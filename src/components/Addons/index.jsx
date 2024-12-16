// vendor
import React from "react";
import { PanelBody, PanelRow, ToggleControl } from "@wordpress/components";

// source
import { useGeneral } from "../../providers/Settings";

export default function Addons() {
  const __ = wp.i18n.__;

  const [general, patch] = useGeneral();

  const toggle = (addon) =>
    patch({
      ...general,
      addons: {
        ...general.addons,
        [addon]: !general.addons[addon],
      },
    });

  return (
    <PanelBody title={__("Addons", "forms-bridge")} initialOpen={false}>
      {Object.entries(general.addons).map(([addon, enabled]) => {
        return (
          <PanelRow key={addon}>
            <ToggleControl
              __nextHasNoMarginBottom
              label={addon}
              checked={enabled}
              onChange={() => toggle(addon)}
            />
          </PanelRow>
        );
      })}
    </PanelBody>
  );
}
