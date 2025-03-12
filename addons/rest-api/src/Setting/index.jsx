// source
import Bridges from "../../../../src/components/Bridges";
import RestBridge from "./Bridge";
import useRestApi from "../hooks/useRestApi";

const { PanelRow } = wp.components;

export default function RestSetting() {
  const [{ bridges, templates }, save] = useRestApi();

  const update = (field) => save({ bridges, templates, ...field });

  return (
    <PanelRow>
      <Bridges
        bridges={bridges}
        setBridges={(bridges) => update({ bridges })}
        Bridge={RestBridge}
      />
    </PanelRow>
  );
}
