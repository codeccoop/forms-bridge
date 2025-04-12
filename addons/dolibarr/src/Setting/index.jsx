// source
import Bridges from "../../../../src/components/Bridges";
import DolibarrBridge from "./Bridge";
import useDolibarrApi from "../hooks/useDolibarrApi";

const { PanelRow } = wp.components;
const { __ } = wp.i18n;

export default function DolibarrSetting() {
  const [{ bridges, templates, workflow_jobs }, save] = useDolibarrApi();

  const update = (field) =>
    save({ bridges, templates, workflow_jobs, ...field });

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
    </>
  );
}
