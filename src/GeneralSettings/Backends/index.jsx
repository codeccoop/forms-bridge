// vendor
import React from "react";
import { __ } from "@wordpress/i18n";
import { TabPanel } from "@wordpress/components";
import { useState } from "@wordpress/element";

// source
import Backend from "./Backend";

export default function Backends({ backends, setBackends }) {
  const __ = wp.i18n.__;

  const [currentTab, setCurrentTab] = useState(backends[0]?.name || "add");
  const tabs = backends
    .map(({ name, base_url, headers }) => ({
      name,
      title: __(name, "forms-bridge"),
      base_url,
      headers,
    }))
    .concat([
      {
        title: __("Add Backend", "forms-bridge"),
        name: "add",
      },
    ]);

  const updateBackend = (index, data) => {
    if (index === -1) index = backends.length;
    const newBackends = backends
      .slice(0, index)
      .concat([data])
      .concat(backends.slice(index + 1, backends.length));

    newBackends.forEach((backend) => delete backend.title);
    setBackends(newBackends);
    setCurrentTab(newBackends[index].name);
  };

  const removeBackend = ({ name }) => {
    const index = backends.findIndex((b) => b.name === name);
    const newBackends = backends
      .slice(0, index)
      .concat(backends.slice(index + 1));
    setBackends(newBackends);
    setCurrentTab(newBackends[index - 1]?.name || "add");
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
        {__("Backends", "forms-bridge")}
      </label>
      <TabPanel
        tabs={tabs}
        onSelect={setCurrentTab}
        initialTabName={currentTab}
      >
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
