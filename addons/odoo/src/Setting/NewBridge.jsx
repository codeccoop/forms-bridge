// source
import NewBridge from "../../../../src/components/Bridges/NewBridge";
import useOdooApi from "../hooks/useOdooApi";
import OdooTemplateWizard from "./TemplateWizard";

const { TextControl, SelectControl } = wp.components;
const { __ } = wp.i18n;

export default function NewOdooBridge({ add, schema }) {
  const [{ databases }] = useOdooApi();
  const dbOptions = [{ label: "", value: "" }].concat(
    databases.map(({ name }) => ({
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
              label={__("Database", "forms-bridge")}
              help={
                databases.length === 0
                  ? __(
                      "Configure, at least, one database access on the panel below"
                    )
                  : ""
              }
              value={data.database || ""}
              onChange={(database) => update({ ...data, database })}
              options={dbOptions}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </div>
          <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
            <TextControl
              label={__("Model", "forms-bridge")}
              value={data.model || ""}
              onChange={(model) => update({ ...data, model })}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </div>
        </>
      )}
    </NewBridge>
  );
}
