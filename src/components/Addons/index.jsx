// source
import { useGeneral } from "../../providers/Settings";

const { PanelBody, PanelRow, ToggleControl } = wp.components;
const { __ } = wp.i18n;

export default function Addons() {
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
              label={__(addon, "forms-bridge")}
              checked={enabled}
              onChange={() => toggle(addon)}
              __nextHasNoMarginBottom
            />
          </PanelRow>
        );
      })}
    </PanelBody>
  );
}
