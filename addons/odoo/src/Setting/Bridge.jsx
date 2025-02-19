// source
import Bridge from "../../../../src/components/Bridges/Bridge";
import NewOdooBridge from "./NewBridge";
import useOdooApi from "../hooks/useOdooApi";

const { TextControl, SelectControl } = wp.components;
const { __ } = wp.i18n;

export default function OdooBridge({ data, update, remove }) {
  const [{ databases }] = useOdooApi();
  const dbOptions = [{ label: "", value: "" }].concat(
    databases.map(({ name }) => ({
      label: name,
      value: name,
    }))
  );

  return (
    <Bridge
      data={data}
      update={update}
      remove={remove}
      template={({ add, schema }) => (
        <NewOdooBridge add={add} schema={schema} />
      )}
      schema={["name", "form_id", "model", "database"]}
    >
      {({ data, update }) => (
        <>
          <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
            <SelectControl
              label={__("Database", "forms-bridge")}
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
    </Bridge>
  );
}
