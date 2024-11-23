// vendor
import React from "react";
import { TabPanel } from "@wordpress/components";

// source
import FormHook from "./FormHook";

export default function FormHooks({ hooks, setHooks }) {
  const __ = wp.i18n.__;
  const tabs = hooks
    .map(({ backend, method, endpoint, form_id, name, pipes }) => ({
      name,
      title: name,
      backend,
      method,
      endpoint,
      form_id,
      pipes,
    }))
    .concat([
      {
        title: __("Add Form", "forms-bridge"),
        name: "add",
      },
    ]);

  const updateHook = (index, data) => {
    if (index === -1) index = hooks.length;
    const newHooks = hooks
      .slice(0, index)
      .concat([data])
      .concat(hooks.slice(index + 1, hooks.length));
    setHooks(newHooks);
  };

  const removeHook = ({ name }) => {
    const index = hooks.findIndex((h) => h.name === name);
    const newHooks = hooks.slice(0, index).concat(hooks.slice(index + 2));
    setHooks(newHooks);
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
        {__("Form Hooks", "forms-bridge")}
      </label>
      <TabPanel tabs={tabs}>
        {(hook) => (
          <FormHook
            {...hook}
            remove={removeHook}
            update={(data) =>
              updateHook(
                hooks.findIndex(({ name }) => name === hook.name),
                data
              )
            }
          />
        )}
      </TabPanel>
    </div>
  );
}
