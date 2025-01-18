// vendor
import React from "react";
import { TextControl, SelectControl } from "@wordpress/components";

// source
import FormHook from "../../../../src/components/FormHooks/FormHook";
import NewOdooFormHook from "./NewFormHook";
import useOdooApi from "../hooks/useOdooApi";

export default function OdooFormHook({ data, update, remove }) {
  const __ = wp.i18n.__;

  const [{ databases }] = useOdooApi();
  const dbOptions = [{ label: "", value: "" }].concat(
    databases.map(({ name }) => ({
      label: name,
      value: name,
    }))
  );

  return (
    <FormHook
      data={data}
      update={update}
      remove={remove}
      template={({ add, schema }) => (
        <NewOdooFormHook add={add} schema={schema} />
      )}
      schema={["name", "form_id", "model", "database"]}
    >
      {({ data, update }) => (
        <>
          <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
            <TextControl
              label={__("Model", "forms-bridge")}
              value={data.model || ""}
              onChange={(model) => update({ ...data, model })}
              __nextHasNoMarginBottom
            />
          </div>
          <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
            <SelectControl
              label={__("Database", "forms-bridge")}
              value={data.database || ""}
              onChange={(database) => update({ ...data, database })}
              options={dbOptions}
              __nextHasNoMarginBottom
            />
          </div>
        </>
      )}
    </FormHook>
  );
}
