// source
import { useGeneral } from "../../providers/Settings";

// logos
import dolibarrLogo from "../../../addons/dolibarr/assets/logo.png";
import financoopLogo from "../../../addons/financoop/assets/logo.png";
import googleSheetsLogo from "../../../addons/google-sheets/assets/logo.png";
import odooLogo from "../../../addons/odoo/assets/logo.png";
import restLogo from "../../../addons/rest-api/assets/logo.png";
import zohoLogo from "../../../addons/zoho/assets/logo.png";

const LOGOS = {
  "dolibarr": dolibarrLogo,
  "financoop": financoopLogo,
  "google-sheets": googleSheetsLogo,
  "odoo": odooLogo,
  "rest-api": restLogo,
  "zoho": zohoLogo,
};

const {
  PanelBody,
  ToggleControl,
  __experimentalSpacer: Spacer,
  Tooltip,
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
        <div key={i} style={{ display: "flex", justifyContent: "left" }}>
          {Object.entries(row).map(([addon, enabled]) => (
            <div style={{ width: "300px", display: "flex", padding: "20px 0" }}>
              <ToggleControl
                checked={enabled}
                onChange={() => toggle(addon)}
                __nextHasNoMarginBottom
              />
              <Tooltip text={__(addon, "forms-bridge")}>
                <img
                  alt={addon}
                  src={"data:image/png;base64," + LOGOS[addon]}
                  height="30px"
                  width="95px"
                  style={{
                    marginTop: "-8px",
                    objectFit: "contain",
                    objectPosition: "left",
                    marginLeft: "5px",
                  }}
                />
              </Tooltip>
            </div>
          ))}
        </div>
      ))}
    </PanelBody>
  );
}
