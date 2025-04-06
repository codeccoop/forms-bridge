// source
import Bridges from "../../../../src/components/Bridges";
import FinanCoopBridge from "./Bridge";
import useFinanCoopApi from "../hooks/useFinanCoopApi";

const { PanelRow } = wp.components;
const { __ } = wp.i18n;

export default function FinancoopSetting() {
  const [{ bridges, templates, workflow_jobs }, save] = useFinanCoopApi();

  const update = (field) =>
    save({ bridges, templates, workflow_jobs, ...field });

  return (
    <>
      <p style={{ marginTop: 0 }}>
        {__(
          "Bridge your forms to the FinanCoop Odoo module and receive capital contributions, loans and donations from your website",
          "forms-bridge"
        )}
      </p>
      <PanelRow>
        <Bridges
          bridges={bridges}
          setBridges={(bridges) => update({ bridges })}
          Bridge={FinanCoopBridge}
        />
      </PanelRow>
    </>
  );
}
