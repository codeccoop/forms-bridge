// source
import { useGeneral } from "../../providers/Settings";

const {
  PanelBody,
  ToggleControl,
  __experimentalSpacer: Spacer,
} = wp.components;
const { useMemo } = wp.element;
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

  const table = useMemo(() => {
    const colLength = Object.keys(general.addons).length / 2;
    return Object.keys(general.addons).reduce((rows, addon, i) => {
      const rowIndex = i % colLength;
      const row = rows[rowIndex] || {};
      row[addon] = general.addons[addon];
      rows[rowIndex] = row;
      return rows;
    }, []);
  }, [general.addons]);

  return (
    <PanelBody title={__("Addons", "forms-bridge")} initialOpen={false}>
      <p>
        {__(
          "Each addon allows you to create API specific bridges and comes with a library of bridge templates and workflow jobs",
          "forms-bridge"
        )}
      </p>
      <Spacer paddingBottom="5px" />
      {table.map((row, i) => (
        <div
          key={i}
          style={{ display: "flex", justifyContent: "left", height: "2em" }}
        >
          {Object.entries(row).map(([addon, enabled]) => (
            <div style={{ width: "300px" }}>
              <ToggleControl
                label={__(addon, "forms-bridge")}
                checked={enabled}
                onChange={() => toggle(addon)}
                __nextHasNoMarginBottom
              />
            </div>
          ))}
        </div>
      ))}
    </PanelBody>
  );
}
