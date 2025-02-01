// vendor
import React from "react";
import { TextControl, SelectControl } from "@wordpress/components";

// source
import NewFormHook from "../../../../src/components/FormHooks/NewFormHook";
import { useSpreadsheets } from "../providers/Spreadsheets";

export default function NewGSFormHook({ add, schema }) {
  const __ = wp.i18n.__;

  const spreadsheets = useSpreadsheets();
  const sheetOptions = [{ label: "", value: "" }].concat(
    spreadsheets.map(({ title, id }) => ({
      label: title,
      value: id,
    }))
  );

  return (
    <NewFormHook add={add} schema={schema}>
      {({ data, update }) => (
        <>
          <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
            <SelectControl
              label={__("Spreadsheet", "forms-bridge")}
              help={
                spreadsheets.length === 0
                  ? __(
                      "Before you can use spreadsheet hooks, you have to grant access to Forms Bridge as OAuth client",
                      "forms-bridge"
                    )
                  : ""
              }
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
    </NewFormHook>
  );
}
