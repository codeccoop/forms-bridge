// source
import Bridges from "../../../../src/components/Bridges";
import DolibarrBridge from "./Bridge";
import useDolibarrApi from "../hooks/useDolibarrApi";
import ApiKeys from "../components/ApiKeys";

const { PanelBody, PanelRow, __experimentalSpacer: Spacer } = wp.components;
const { __ } = wp.i18n;

export default function DolibarrSetting() {
  const [{ api_keys, bridges, templates, workflow_jobs }, save] =
    useDolibarrApi();

  const update = (field) =>
    save({ api_keys, bridges, templates, workflow_jobs, ...field });

  return (
    <>
      <p style={{ marginTop: 0 }}>
        {__(
          "Bridge your forms to Dolibarr and convert user responses to registries on your ERP",
          "forms-bridge"
        )}
      </p>
      <PanelRow>
        <Bridges
          bridges={bridges}
          setBridges={(bridges) => update({ bridges })}
          Bridge={DolibarrBridge}
        />
      </PanelRow>
      <Spacer paddingY="calc(8px)" />
      <PanelBody
        title={__("API keys", "forms-bridge")}
        initialOpen={api_keys.length === 0}
      >
        <p>
          {__(
            "Store your Dolibarr API keys and reuse them on your Dolibarr bridges",
            "forms-bridge"
          )}
        </p>
        <ApiKeys
          apiKeys={api_keys}
          setApiKeys={(api_keys) => update({ api_keys })}
        />
      </PanelBody>
    </>
  );
}
