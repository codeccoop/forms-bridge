// source
import Credential from "./Credential";
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

export default function Credentials({ credentials, setCredentials }) {
  const [currentTab, setCurrentTab] = useState(
    String(credentials.length ? 0 : -1)
  );
  const [tabFocus, setTabFocus] = useState(null);
  const tabs = credentials
    .map(({ name, database, user, password }, i) => ({
      name: String(i),
      title: name,
      database,
      user,
      password,
      icon: (
        <TabTitle
          name={name}
          focus={tabFocus === name}
          setFocus={(value) => setTabFocus(value ? name : null)}
          copy={() => copyCredential(name)}
        />
      ),
    }))
    .concat([
      {
        name: "-1",
        title: __("Add credential", "forms-bridge"),
      },
    ]);

  const credentialCount = useRef(credentials.length);
  useEffect(() => {
    if (credentials.length > credentialCount.current) {
      setCurrentTab(String(credentials.length - 1));
    } else if (credentials.length < credentialCount.current) {
      setCurrentTab(String(currentTab - 1));
    }

    return () => {
      credentialCount.current = credentials.length;
    };
  }, [credentials]);

  const updateCredentials = (index, data) => {
    if (index === -1) index = credentials.length;
    const newcredentials = credentials
      .slice(0, index)
      .concat([data])
      .concat(credentials.slice(index + 1, credentials.length));

    newcredentials.forEach((db) => {
      delete db.title;
      delete db.icon;
    });
    setCredentials(newcredentials);
  };

  const removeCredential = ({ name }) => {
    const index = credentials.findIndex((db) => db.name === name);
    const newcredentials = credentials
      .slice(0, index)
      .concat(credentials.slice(index + 1));
    setCredentials(newcredentials);
  };

  const copyCredential = (name) => {
    const i = credentials.findIndex((db) => db.name === name);
    const db = credentials[i];
    const copy = { ...db };

    let isUnique = false;
    if (!isUnique) {
      copy.name += "-copy";
      isUnique = credentials.find((db) => db.name === copy.name) === undefined;
    }

    setCredentials(credentials.concat(copy));
  };

  return (
    <div style={{ width: "100%" }}>
      <p>
        {__(
          "Configure RPC credentials to access your ERP database models",
          "forms-bridge"
        )}
      </p>
      <TabPanel
        tabs={tabs}
        onSelect={setCurrentTab}
        initialTabName={currentTab}
      >
        {(credential) => {
          credential.name =
            credential.name >= 0 ? credentials[+credential.name].name : "add";
          return (
            <Credential
              data={credential}
              remove={removeCredential}
              update={(data) =>
                updateCredentials(
                  credentials.findIndex(({ name }) => name === credential.name),
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
