// source
import Bridges from "../../../../src/components/Bridges";
import ZohoBridge from "./Bridge";
import useZohoApi from "../hooks/useZohoApi";
import Credentials from "../components/Credentials";

const { PanelBody, PanelRow, __experimentalSpacer: Spacer } = wp.components;
const { __ } = wp.i18n;

export default function ZohoSetting() {
  const [{ bridges, templates, credentials, workflow_jobs }, save] =
    useZohoApi();

  const update = (field) =>
    save({ bridges, templates, credentials, workflow_job, ...field });

  return (
    <>
      <PanelRow>
        <Bridges
          bridges={bridges}
          setBridges={(bridges) => update({ bridges })}
          Bridge={ZohoBridge}
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
