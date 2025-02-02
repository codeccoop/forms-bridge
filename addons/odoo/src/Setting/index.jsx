// source
import FormHooks from "../../../../src/components/FormHooks";
import OdooFormHook from "./FormHook";
import useOdooApi from "../hooks/useOdooApi";
import Databases from "../components/Databases";

const { PanelBody, PanelRow, __experimentalSpacer: Spacer } = wp.components;
const { __ } = wp.i18n;

export default function OdooSetting() {
  const [{ databases, form_hooks: hooks }, save] = useOdooApi();

  const update = (field) => save({ databases, form_hooks: hooks, ...field });

  return (
    <>
      <PanelRow>
        <FormHooks
          hooks={hooks}
          setHooks={(form_hooks) => update({ form_hooks })}
          FormHook={OdooFormHook}
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
