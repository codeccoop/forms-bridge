// source
import Bridge from "../../../../src/components/Bridges/Bridge";
import NewGSBridge from "./NewBridge";
import { useSpreadsheets } from "../providers/Spreadsheets";

const { TextControl, SelectControl } = wp.components;
const { __ } = wp.i18n;

export default function GFBridge({ data, update, remove }) {
  const spreadsheets = useSpreadsheets();
  const sheetOptions = [{ label: "", value: "" }].concat(
    spreadsheets.map(({ title, id }) => ({
      label: title,
      value: id,
    }))
  );

  return (
    <Bridge
      data={data}
      update={update}
      remove={remove}
      template={({ add, schema }) => <NewGSBridge add={add} schema={schema} />}
      schema={["name", "form_id", "spreadsheet", "tab"]}
    >
      {({ data, update }) => (
        <>
          <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
            <SelectControl
              label={__("Spreadsheet", "forms-bridge")}
              value={data.spreadsheet || ""}
              onChange={(spreadsheet) => update({ ...data, spreadsheet })}
              options={sheetOptions}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </div>
          <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
            <TextControl
              label={__("Tab Name", "forms-bridge")}
              value={data.tab || ""}
              onChange={(tab) => update({ ...data, tab })}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </div>
        </>
      )}
    </Bridge>
  );
}
