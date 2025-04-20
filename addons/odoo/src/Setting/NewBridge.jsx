// source
import NewBridge from "../../../../src/components/Bridges/NewBridge";
import useOdooApi from "../hooks/useOdooApi";
import OdooTemplateWizard from "./TemplateWizard";

const { TextControl, SelectControl } = wp.components;
const { __ } = wp.i18n;

export default function NewOdooBridge({ add, schema }) {
  const [{ credentials }] = useOdooApi();
  const credentialOptions = [{ label: "", value: "" }].concat(
    credentials.map(({ name }) => ({
      label: name,
      value: name,
    }))
  );

  return (
    <NewBridge add={add} schema={schema} Wizard={OdooTemplateWizard}>
      {({ data, update }) => (
        <>
          <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
            <SelectControl
              label={__("Credential", "forms-bridge")}
              help={
                credentials.length === 0
                  ? __("Configure, at least, one credential on the panel below")
                  : ""
              }
              value={data.credential || ""}
              onChange={(credential) => update({ ...data, credential })}
              options={credentialOptions}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </div>
          <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
            <TextControl
              label={__("Model", "forms-bridge")}
              value={data.endpoint || ""}
              onChange={(endpoint) => update({ ...data, endpoint })}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </div>
        </>
      )}
    </NewBridge>
  );
}
