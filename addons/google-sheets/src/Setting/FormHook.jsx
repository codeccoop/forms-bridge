// vendor
import React from "react";
import { TextControl, SelectControl } from "@wordpress/components";

// source
import FormHook from "../../../../src/components/FormHooks/FormHook";
import NewGSFormHook from "./NewFormHook";
import { useSpreadsheets } from "../providers/Spreadsheets";

export default function OdooFormHook({ data, update, remove }) {
  const __ = wp.i18n.__;

  const spreadsheets = useSpreadsheets();
  const sheetOptions = [{ label: "", value: "" }].concat(
    spreadsheets.map(({ title, id }) => ({
      label: title,
      value: id,
    }))
  );

  return (
    <FormHook
      data={data}
      update={update}
      remove={remove}
      template={({ add, schema }) => (
        <NewGSFormHook add={add} schema={schema} />
      )}
      schema={["name", "form_id", "spreadsheet", "tab"]}
    >
      {({ data, update }) => (
        <>
          <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
            <SelectControl
              label={__("Spreadsheet", "forms-bridge")}
              value={data.spreadsheet}
              onChange={(spreadsheet) => update({ ...data, spreadsheet })}
              options={sheetOptions}
              __nextHasNoMarginBottom
            />
          </div>
          <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
            <TextControl
              label={__("Tab Name", "forms-bridge")}
              value={data.tab}
              onChange={(tab) => update({ ...data, tab })}
              __nextHasNoMarginBottom
            />
          </div>
        </>
      )}
    </FormHook>
  );
}
