// vendor
import React from "react";
import { TabPanel } from "@wordpress/components";
import { useState } from "@wordpress/element";

// source
import FormHook from "./FormHook";

const CopyIcon = ({ onClick }) => {
  const [focus, setFocus] = useState(false);

  return (
    <svg
      width="24px"
      height="24px"
      viewBox="0 0 24 24"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
      style={{
        position: "absolute",
        zIndex: 1,
        top: "-4px",
        right: "-5px",
      }}
      onMouseEnter={() => setFocus(true)}
      onMouseLeave={() => setFocus(false)}
      onClick={() => onClick()}
    >
      <path
        fillRule="evenodd"
        clipRule="evenodd"
        d="M17.676 14.248C17.676 15.8651 16.3651 17.176 14.748 17.176H7.428C5.81091 17.176 4.5 15.8651 4.5 14.248V6.928C4.5 5.31091 5.81091 4 7.428 4H14.748C16.3651 4 17.676 5.31091 17.676 6.928V14.248Z"
        stroke={focus ? "#3858e9" : "#000000"}
        strokeWidth="1.5"
        strokeLinecap="round"
        strokeLinejoin="round"
        fill="#ffffff"
      />
      <path
        d="M10.252 20H17.572C19.1891 20 20.5 18.689 20.5 17.072V9.75195"
        stroke={focus ? "#3858e9" : "#000000"}
        strokeWidth="1.5"
        strokeLinecap="round"
        strokeLinejoin="round"
        fill="#ffffff00"
      />
    </svg>
  );
};

function TabTitle({ name, focus, setFocus, copy }) {
  return (
    <div
      style={{ position: "relative", padding: "0px 24px 0px 10px" }}
      onMouseEnter={() => setFocus(true)}
      onMouseLeave={() => setFocus(false)}
    >
      <span>{name}</span>
      {focus && <CopyIcon onClick={copy} />}
    </div>
  );
}

export default function FormHooks({ hooks, setHooks }) {
  const __ = wp.i18n.__;

  const [currentTab, setCurrentTab] = useState(hooks[0]?.name || "add");
  const [tabFocus, setTabFocus] = useState(null);
  const tabs = hooks
    .map(({ backend, model, form_id, name, pipes }) => ({
      name,
      title: name,
      form_id,
      model,
      backend,
      pipes,
      icon: (
        <TabTitle
          name={name}
          focus={tabFocus === name}
          setFocus={(value) => setTabFocus(value ? name : null)}
          copy={() => copyHook(name)}
        />
      ),
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

    newHooks.forEach((hook) => {
      delete hook.title;
      delete hook.icon;
    });
    setHooks(newHooks);
    setCurrentTab(newHooks[index].name);
  };

  const removeHook = ({ name }) => {
    const index = hooks.findIndex((h) => h.name === name);
    const newHooks = hooks.slice(0, index).concat(hooks.slice(index + 1));
    setHooks(newHooks);
    setCurrentTab(newHooks[index - 1]?.name || "add");
  };

  const copyHook = (name) => {
    const i = hooks.findIndex((h) => h.name === name);
    const hook = {
      name: hooks[i].name,
      form_id: hooks[i].form_id,
      model: hooks[i].model,
      backend: hooks[i].backend,
      pipes: JSON.parse(JSON.stringify(hooks[i].pipes)),
    };

    let isUnique = false;
    while (!isUnique) {
      hook.name += "-copy";
      isUnique = hooks.find((h) => h.name === hook.name) === undefined;
    }

    setHooks(hooks.concat(hook));
    setCurrentTab(hook.name);
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
      <TabPanel
        tabs={tabs}
        onSelect={setCurrentTab}
        initialTabName={currentTab}
      >
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
