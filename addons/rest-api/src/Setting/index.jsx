// source
import Bridges from "../../../../src/components/Bridges";
import RestBridge from "./Bridge";
import useRestApi from "../hooks/useRestApi";

const { PanelRow } = wp.components;
const { __ } = wp.i18n;

export default function RestSetting() {
  const [{ bridges, templates, workflow_jobs }, save] = useRestApi();

  const update = (field) =>
    save({ bridges, templates, workflow_jobs, ...field });

  return (
    <>
      <p style={{ marginTop: 0 }}>
        {__(
          "Bridge your forms to any backend or service with a REST API",
          "forms-bridge"
        )}
      </p>
      <PanelRow>
        <Bridges
          bridges={bridges}
          setBridges={(bridges) => update({ bridges })}
          Bridge={RestBridge}
        />
      </PanelRow>
    </>
  );
}
