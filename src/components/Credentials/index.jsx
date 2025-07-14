// source
import Credential from "../Credential";
import NewCredential from "../Credential/NewCredential";
import CopyIcon from "../CopyIcon";
import { useCredentials } from "../../hooks/useAddon";
import { useSchemas } from "../../providers/Schemas";
import useTab from "../../hooks/useTab";

const { PanelBody, TabPanel } = wp.components;
const { useState } = wp.element;
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

export default function Credentials() {
  const [addon] = useTab();

  const { credential: schema } = useSchemas();
  const [credentials, setCredentials] = useCredentials();

  const [tabFocus, setTabFocus] = useState(null);

  const tabs = credentials
    .map(({ name }, index) => ({
      index,
      name: String(index),
      title: name,
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
        index: -1,
        name: "new",
        title: __("Add credential", "forms-bridge"),
      },
    ]);

  const updateCredential = (index, data) => {
    if (index === -1) index = credentials.length;
    const newcredentials = credentials
      .slice(0, index)
      .concat([data])
      .concat(credentials.slice(index + 1, credentials.length));

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
    const credential = credentials[i];
    const copy = { ...credential };

    let isUnique = false;
    if (!isUnique) {
      copy.name += "-copy";
      isUnique =
        credentials.find(({ name }) => name === copy.name) === undefined;
    }

    setCredentials(credentials.concat(copy));
  };

  if (!schema) return null;

  return (
    <PanelBody title={__("Credentials", "forms-bridge")} initialOpen={false}>
      <div style={{ width: "100%" }}>
        <p>{schema.description || ""}</p>
        <TabPanel tabs={tabs}>
          {(tab) => {
            const credential = credentials[tab.index];

            if (!credential) {
              return (
                <NewCredential
                  add={(data) => updateCredential(tab.index, data)}
                  schema={schema}
                />
              );
            }
            return (
              <Credential
                addon={addon}
                data={credential}
                schema={schema}
                remove={removeCredential}
                update={(data) => updateCredential(tab.index, data)}
              />
            );
          }}
        </TabPanel>
      </div>
    </PanelBody>
  );
}
