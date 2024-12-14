// vendor
import React from "react";
import { TextControl, SelectControl } from "@wordpress/components";
import { useEffect } from "@wordpress/element";

// source
import NewFormHook from "../../../components/FormHooks/NewFormHook";

const methodOptions = [
  {
    label: "GET",
    value: "GET",
  },
  {
    label: "POST",
    value: "POST",
  },
  {
    label: "PUT",
    value: "PUT",
  },
  {
    label: "DELETE",
    value: "DELETE",
  },
];

export default function NewRestFormHook({ add, schema }) {
  const __ = wp.i18n.__;

  return (
    <NewFormHook add={add} schema={schema}>
      {({ data, update }) => (
        <>
          <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
            <SelectControl
              label={__("Method", "forms-bridge")}
              value={data.method || "POST"}
              onChange={(method) => update({ ...data, method })}
              options={methodOptions}
              __nextHasNoMarginBottom
            />
          </div>
          <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
            <TextControl
              label={__("Endpoint", "forms-bridge")}
              value={data.endpoint || ""}
              onChange={(endpoint) => update({ ...data, endpoint })}
              __nextHasNoMarginBottom
            />
          </div>
        </>
      )}
    </NewFormHook>
  );
}
