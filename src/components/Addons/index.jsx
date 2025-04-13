// source
import { useGeneral } from "../../providers/Settings";

// logos
import biginLogo from "../../../addons/bigin/assets/logo.png";
import brevoLogo from "../../../addons/brevo/assets/logo.png";
import dolibarrLogo from "../../../addons/dolibarr/assets/logo.png";
import financoopLogo from "../../../addons/financoop/assets/logo.png";
import googleSheetsLogo from "../../../addons/google-sheets/assets/logo.png";
import listmonkLogo from "../../../addons/listmonk/assets/logo.png";
import mailchimpLogo from "../../../addons/mailchimp/assets/logo.png";
import odooLogo from "../../../addons/odoo/assets/logo.png";
import restLogo from "../../../addons/rest-api/assets/logo.png";
import zohoLogo from "../../../addons/zoho/assets/logo.png";

const LOGOS = {
  "bigin": biginLogo,
  "brevo": brevoLogo,
  "dolibarr": dolibarrLogo,
  "financoop": financoopLogo,
  "google-sheets": googleSheetsLogo,
  "listmonk": listmonkLogo,
  "mailchimp": mailchimpLogo,
  "odoo": odooLogo,
  "rest-api": restLogo,
  "zoho": zohoLogo,
};

const { PanelBody, __experimentalSpacer: Spacer } = wp.components;
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
      <p>
        {__(
          "Each addon allows you to create API specific bridges and comes with a library of bridge templates and workflow jobs",
          "forms-bridge"
        )}
      </p>
      <Spacer paddingBottom="5px" />
      <div style={{ display: "flex", flexWrap: "wrap", gap: "20px" }}>
        {Object.keys(general.addons).map((addon) => (
          <button
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
              color: general.addons[addon]
                ? "var(--wp-components-color-accent,var(--wp-admin-theme-color,#3858e9))"
                : "inherit",
              border: general.addons[addon] ? "2px solid" : "none",
            }}
            onClick={() => toggle(addon)}
          >
            <div style={{ flex: 1, display: "flex", alignItems: "center" }}>
              <img
                alt={addon}
                src={"data:image/png;base64," + LOGOS[addon]}
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
            <h4 style={{ margin: 0, fontSize: "1rem" }}>
              {__(addon, "forms-bridge")}
            </h4>
          </button>
        ))}
      </div>
      <Spacer paddingY="calc(10px)" />
    </PanelBody>
  );
}
