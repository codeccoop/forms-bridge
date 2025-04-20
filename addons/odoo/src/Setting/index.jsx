// source
import Bridges from "../../../../src/components/Bridges";
import OdooBridge from "./Bridge";
import useOdooApi from "../hooks/useOdooApi";
import Credentials from "../components/Credentials";

const { PanelBody, PanelRow, __experimentalSpacer: Spacer } = wp.components;
const { __ } = wp.i18n;

export default function OdooSetting() {
  const [{ credentials, bridges, templates, workflow_jobs }, save] =
    useOdooApi();

  const update = (field) =>
    save({ credentials, bridges, templates, workflow_jobs, ...field });

  return (
    <>
      <p style={{ marginTop: 0 }}>
        {__(
          "Bridge your forms to Odoo and convert user responses to registries on your ERP",
          "forms-bridge"
        )}
      </p>
      <PanelRow>
        <Bridges
          credentials={credentials}
          bridges={bridges}
          setBridges={(bridges) => update({ bridges })}
          Bridge={OdooBridge}
        />
      </PanelRow>
      <Spacer paddingY="calc(8px)" />
      <PanelBody
        title={__("Credentials", "forms-bridge")}
        initialOpen={credentials.length === 0}
      >
        <Credentials
          credentials={credentials}
          setCredentials={(credentials) => update({ credentials })}
        />
      </PanelBody>
    </>
  );
}
