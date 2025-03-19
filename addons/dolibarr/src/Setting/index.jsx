// source
import Bridges from "../../../../src/components/Bridges";
import DolibarrBridge from "./Bridge";
import useDolibarrApi from "../hooks/useDolibarrApi";
import ApiKeys from "../components/ApiKeys";

const { PanelBody, PanelRow, __experimentalSpacer: Spacer } = wp.components;
const { __ } = wp.i18n;

export default function DolibarrSetting() {
  const [{ api_keys, bridges, templates }, save] = useDolibarrApi();

  const update = (field) => save({ api_keys, bridges, templates, ...field });

  return (
    <>
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
        <ApiKeys
          apiKeys={api_keys}
          setApiKeys={(api_keys) => update({ api_keys })}
        />
      </PanelBody>
    </>
  );
}
