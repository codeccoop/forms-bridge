// vendor
import React from "react";
import { __ } from "@wordpress/i18n";
import { TabPanel } from "@wordpress/components";

// source
import Backend from "./Backend";

export default function Backends({ backends, setBackends }) {
  const tabs = backends
    .map(({ name, base_url, headers }) => ({
      name,
      title: name,
      base_url,
      headers,
    }))
    .concat([
      {
        title: __("Add Backend", "wpct-erp-forms"),
        name: "add",
      },
    ]);

  const updateBackend = (index, data) => {
    if (index === -1) index = backends.length;
    const newBackends = backends
      .slice(0, index)
      .concat([data])
      .concat(backends.slice(index + 1, backends.length));
    setBackends(newBackends);
  };

  const removeBackend = ({ name }) => {
    const index = backends.findIndex((b) => b.name === name);
    const newBackends = backends
      .slice(0, index)
      .concat(backends.slice(index + 2));
    setBackends(newBackends);
  };

  return (
    <div style={{ width: "100%" }}>
      <label
        className="components-base-control__label"
        style={{
          fontSize: "11px",
          textTransform: "uppercase",
          fontWeight: 500,
          marginBottom: "calc(8px)",
        }}
      >
        {__("Backends", "wpct-erp-forms")}
      </label>
      <TabPanel tabs={tabs}>
        {(backend) => (
          <Backend
            {...backend}
            remove={removeBackend}
            update={(newBackend) =>
              updateBackend(
                backends.findIndex(({ name }) => name === backend.name),
                newBackend
              )
            }
          />
        )}
      </TabPanel>
    </div>
  );
}
