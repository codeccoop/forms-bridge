// vendor
import React from "react";
import { __ } from "@wordpress/i18n";
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

export default function Backends({ backends, setBackends, Backend }) {
  const __ = wp.i18n.__;

  const [currentTab, setCurrentTab] = useState(
    String(backends.length ? 0 : -1)
  );
  const [tabFocus, setTabFocus] = useState(null);
  const tabs = backends
    .map(({ name, base_url, headers }, i) => ({
      name: String(i),
      title: __(name, "forms-bridge"),
      base_url,
      headers,
      icon: (
        <TabTitle
          name={name}
          focus={tabFocus === name}
          setFocus={(value) => setTabFocus(value ? name : null)}
          copy={() => copyBackend(name)}
        />
      ),
    }))
    .concat([
      {
        name: "-1",
        title: __("Add Backend", "forms-bridge"),
      },
    ]);

  const backendsCount = useRef(backends.length);
  useEffect(() => {
    if (backends.length > backendsCount.current) {
      setCurrentTab(String(backends.length - 1));
    } else if (backends.length < backendsCount.current) {
      setCurrentTab(String(currentTab - 1));
    }

    return () => {
      backendsCount.current = backends.length;
    };
  }, [backends]);

  const updateBackend = (index, data) => {
    if (index === -1) index = backends.length;
    const newBackends = backends
      .slice(0, index)
      .concat([data])
      .concat(backends.slice(index + 1, backends.length));

    newBackends.forEach((backend) => {
      delete backend.title;
      delete backend.icon;
    });
    setBackends(newBackends);
  };

  const removeBackend = ({ name }) => {
    const index = backends.findIndex((b) => b.name === name);
    const newBackends = backends
      .slice(0, index)
      .concat(backends.slice(index + 1));
    setBackends(newBackends);
  };

  const copyBackend = (name) => {
    const i = backends.findIndex((backend) => backend.name === name);
    const backend = backends[i];
    const copy = { ...backend };

    let isUnique = false;
    if (!isUnique) {
      copy.name += "-copy";
      isUnique =
        backends.find((backend) => backend.name === copy.name) === undefined;
    }

    setBackends(backends.concat(copy));
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
        {(backend) => {
          backend.name =
            backend.name >= 0 ? backends[+backend.name].name : "add";
          return (
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
          );
        }}
      </TabPanel>
    </div>
  );
}
