// source
import Bridge from "../../../../src/components/Bridges/Bridge";
import NewDolibarrBridge from "./NewBridge";

const { TextControl, SelectControl } = wp.components;
const { __ } = wp.i18n;

export default function DolibarrBridge({ data, update, remove }) {
  return (
    <Bridge
      data={data}
      update={update}
      remove={remove}
      schema={["name", "backend", "form_id", "endpoint"]}
      template={({ add, schema }) => (
        <NewDolibarrBridge add={(data) => add(data)} schema={schema} />
      )}
    >
      {({ data, update }) => (
        <>
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
