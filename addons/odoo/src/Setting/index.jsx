// source
import Bridges from "../../../../src/components/Bridges";
import OdooBridge from "./Bridge";
import useOdooApi from "../hooks/useOdooApi";
import Databases from "../components/Databases";

const { PanelBody, PanelRow, __experimentalSpacer: Spacer } = wp.components;
const { __ } = wp.i18n;

export default function OdooSetting() {
  const [{ databases, bridges, templates }, save] = useOdooApi();

  const update = (field) => save({ databases, bridges, templates, ...field });

  return (
    <>
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
        <Databases
          databases={databases}
          setDatabases={(databases) => update({ databases })}
        />
      </PanelBody>
    </>
  );
}
