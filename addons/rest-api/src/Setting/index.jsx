// source
import Bridges from "../../../../src/components/Bridges";
import RestBridge from "./Bridge";
import useRestApi from "../hooks/useRestApi";

const { PanelRow } = wp.components;

export default function RestSetting() {
  const [{ bridges }, save] = useRestApi();

  const update = (bridges) => save({ bridges });

  return (
    <PanelRow>
      <Bridges
        bridges={bridges}
        setBridges={(bridges) => update(bridges)}
        Bridge={RestBridge}
      />
    </PanelRow>
  );
}
