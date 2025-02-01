// vendor
import React from "react";
import { TabPanel } from "@wordpress/components";
import { useState, useEffect, useRef } from "@wordpress/element";

// source
import Database from "./Database";

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

export default function Databases({ databases, setDatabases }) {
  const __ = wp.i18n.__;

  const [currentTab, setCurrentTab] = useState(
    String(databases.length ? 0 : -1)
  );
  const [tabFocus, setTabFocus] = useState(null);
  const tabs = databases
    .map(({ name, user, password, backend }, i) => ({
      name: String(i),
      title: name,
      user,
      password,
      backend,
      icon: (
        <TabTitle
          name={name}
          focus={tabFocus === name}
          setFocus={(value) => setTabFocus(value ? name : null)}
          copy={() => copyDatabase(name)}
        />
      ),
    }))
    .concat([
      {
        name: "-1",
        title: __("Add database", "forms-bridge"),
      },
    ]);

  const dbCount = useRef(databases.length);
  useEffect(() => {
    if (databases.length > dbCount.current) {
      setCurrentTab(String(databases.length - 1));
    } else if (databases.length < dbCount.current) {
      setCurrentTab(String(currentTab - 1));
    }

    return () => {
      dbCount.current = databases.length;
    };
  }, [databases]);

  const updateDatabases = (index, data) => {
    if (index === -1) index = databases.length;
    const newDatabases = databases
      .slice(0, index)
      .concat([data])
      .concat(databases.slice(index + 1, databases.length));

    newDatabases.forEach((db) => {
      delete db.title;
      delete db.icon;
    });
    setDatabases(newDatabases);
  };

  const removeDatabase = ({ name }) => {
    const index = databases.findIndex((db) => db.name === name);
    const newDatabases = databases
      .slice(0, index)
      .concat(databases.slice(index + 1));
    setDatabases(newDatabases);
  };

  const copyDatabase = (name) => {
    const i = databases.findIndex((db) => db.name === name);
    const db = databases[i];
    const copy = { ...db };

    let isUnique = false;
    if (!isUnique) {
      copy.name += "-copy";
      isUnique = databases.find((db) => db.name === copy.name) === undefined;
    }

    setDatabases(databases.concat(copy));
  };

  return (
    <div style={{ width: "100%" }}>
      <TabPanel
        tabs={tabs}
        onSelect={setCurrentTab}
        initialTabName={currentTab}
      >
        {(db) => {
          db.name = db.name >= 0 ? databases[+db.name].name : "add";
          return (
            <Database
              data={db}
              remove={removeDatabase}
              update={(data) =>
                updateDatabases(
                  databases.findIndex(({ name }) => name === db.name),
                  data
                )
              }
              databases={databases}
            />
          );
        }}
      </TabPanel>
    </div>
  );
}
