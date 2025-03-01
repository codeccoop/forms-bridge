// source
import Bridges from "../../../../src/components/Bridges";
import FinanCoopBridge from "./Bridge";
import useFinanCoopApi from "../hooks/useFinanCoopApi";

const { PanelRow } = wp.components;

export default function FinancoopSetting() {
  const [{ bridges, templates }, save] = useFinanCoopApi();

  const update = (field) => save({ bridges, templates, ...field });

  return (
    <PanelRow>
      <Bridges
        bridges={bridges}
        setBridges={(bridges) => update({ bridges })}
        Bridge={FinanCoopBridge}
      />
    </PanelRow>
  );
}
