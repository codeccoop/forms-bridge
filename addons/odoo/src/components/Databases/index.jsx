// source
import Database from "./Database";
import CopyIcon from "../../../../../src/components/CopyIcon";

const { TabPanel } = wp.components;
const { useState, useEffect, useRef } = wp.element;
const { __ } = wp.i18n;

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
            />
          );
        }}
      </TabPanel>
    </div>
  );
}
