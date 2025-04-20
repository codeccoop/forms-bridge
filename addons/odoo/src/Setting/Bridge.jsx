// source
import Bridge from "../../../../src/components/Bridges/Bridge";
import NewOdooBridge from "./NewBridge";
import useOdooApi from "../hooks/useOdooApi";

const { TextControl, SelectControl } = wp.components;
const { __ } = wp.i18n;

export default function OdooBridge({ data, update, remove }) {
  const [{ credentials }] = useOdooApi();
  const credentialOptions = [{ label: "", value: "" }].concat(
    credentials.map(({ name }) => ({
      label: name,
      value: name,
    }))
  );

  return (
    <Bridge
      data={data}
      update={update}
      remove={remove}
      schema={["name", "form_id", "backend", "credential", "endpoint"]}
      template={({ add, schema }) => (
        <NewOdooBridge add={add} schema={schema} />
      )}
    >
      {({ data, update }) => (
        <>
          <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
            <SelectControl
              label={__("Credential", "forms-bridge")}
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
    </Bridge>
  );
}
