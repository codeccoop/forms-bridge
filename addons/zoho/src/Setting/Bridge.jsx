// source
import Bridge from "../../../../src/components/Bridges/Bridge";
import useZohoApi from "../hooks/useZohoApi";
import NewZohoBridge from "./NewBridge";

const { TextControl, SelectControl } = wp.components;
const { __ } = wp.i18n;

export default function ZohoBridge({ data, update, remove }) {
  const [{ credentials }] = useZohoApi();
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
      schema={["name", "form_id", "credential", "scope", "endpoint"]}
      template={({ add, schema }) => (
        <NewZohoBridge add={(data) => add(data)} schema={schema} />
      )}
    >
      {({ data, update }) => (
        <>
          <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
            <SelectControl
              label={__("Credentials", "forms-bridge")}
              value={data.credential || ""}
              onChange={(credential) => update({ ...data, credential })}
              options={credentialOptions}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </div>
          <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
            <TextControl
              label={__("Scope", "forms-bridge")}
              value={data.scope || ""}
              onChange={(scope) => update({ ...data, scope })}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </div>
          <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
            <TextControl
              label={__("Endpoint", "forms-bridge")}
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
