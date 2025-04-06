// source
import Bridges from "../../../../src/components/Bridges";
import OdooBridge from "./Bridge";
import useOdooApi from "../hooks/useOdooApi";
import Databases from "../components/Databases";

const { PanelBody, PanelRow, __experimentalSpacer: Spacer } = wp.components;
const { __ } = wp.i18n;

export default function OdooSetting() {
  const [{ databases, bridges, templates, workflow_jobs }, save] = useOdooApi();

  const update = (field) =>
    save({ databases, bridges, templates, workflow_jobs, ...field });

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
          bridges={bridges}
          setBridges={(bridges) => update({ bridges })}
          Bridge={OdooBridge}
        />
      </PanelRow>
      <Spacer paddingY="calc(8px)" />
      <PanelBody
        title={__("Databases", "forms-bridge")}
        initialOpen={databases.length === 0}
      >
        <p>
          {__(
            "Configure RPC credentials to access your ERP database models",
            "forms-bridge"
          )}
        </p>
        <Databases
          databases={databases}
          setDatabases={(databases) => update({ databases })}
        />
      </PanelBody>
    </>
  );
}
