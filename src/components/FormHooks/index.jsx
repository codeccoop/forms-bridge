// vendor
import React from "react";
import { TabPanel } from "@wordpress/components";
import { useState, useEffect, useRef } from "@wordpress/element";

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

export default function FormHooks({ hooks, setHooks, FormHook }) {
  const __ = wp.i18n.__;

  const [currentTab, setCurrentTab] = useState(String(hooks.length ? 0 : -1));
  const [tabFocus, setTabFocus] = useState(null);
  const tabs = hooks
    .map(({ backend, form_id, name, pipes, ...customFields }, i) => ({
      ...customFields,
      name: String(i),
      title: name,
      backend,
      form_id,
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
        name: "-1",
        title: __("Add Form", "forms-bridge"),
      },
    ]);

  const hookCount = useRef(hooks.length);
  useEffect(() => {
    if (hooks.length > hookCount.current) {
      setCurrentTab(String(hooks.length - 1));
    } else if (hooks.length < hookCount.current) {
      setCurrentTab(String(currentTab - 1));
    }

    return () => {
      hookCount.current = hooks.length;
    };
  }, [hooks]);

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
  };

  const removeHook = ({ name }) => {
    const index = hooks.findIndex((h) => h.name === name);
    const newHooks = hooks.slice(0, index).concat(hooks.slice(index + 1));
    setHooks(newHooks);
  };

  const copyHook = (name) => {
    const i = hooks.findIndex((h) => h.name === name);
    const hook = hooks[i];
    const copy = {
      ...hook,
      pipes: JSON.parse(JSON.stringify(hooks.pipes || [])),
    };

    let isUnique = false;
    while (!isUnique) {
      copy.name += "-copy";
      isUnique = hooks.find((h) => h.name === copy.name) === undefined;
    }

    setHooks(hooks.concat(copy));
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
        {(hook) => {
          hook.name = hook.name >= 0 ? hooks[+hook.name].name : "add";
          return (
            <FormHook
              data={hook}
              remove={removeHook}
              update={(data) =>
                updateHook(
                  hooks.findIndex(({ name }) => name === hook.name),
                  data
                )
              }
            />
          );
        }}
      </TabPanel>
    </div>
  );
}
