// source
import Credential from "../Credential";
import NewCredential from "../Credential/NewCredential";
import useTab from "../../hooks/useTab";
import TabTitle from "../TabTitle";
import AddIcon from "../icons/Add";
import { useSchemas } from "../../providers/Schemas";

const { useEffect, useRef, useMemo } = wp.element;
const { PanelBody, TabPanel } = wp.components;
const { __ } = wp.i18n;

const CSS = `.credentials-tabs-panel>.components-tab-panel__tabs{overflow-x:auto;}
.credentials-tabs-panel>.components-tab-panel__tabs>button{flex-shrink:0;}`;

export default function Credentials({ credentials, setCredentials }) {
  const [addon] = useTab();

  const { credential: schema } = useSchemas();

  const names = useMemo(() => {
    return new Set(credentials.map((c) => c.name));
  }, [credentials]);

  const tabs = credentials
    .map(({ name }, index) => ({
      index,
      name: String(index),
      title: name,
      icon: <TabTitle name={name} />,
    }))
    .concat([
      {
        index: -1,
        name: "new",
        title: __("Add a credential", "forms-bridge"),
        icon: (
          <div style={{ marginBottom: "-2px" }}>
            <AddIcon width="15" height="15" />
          </div>
        ),
      },
    ]);

  const updateCredential = (index, data) => {
    if (index === -1) index = credentials.length;

    data.name = data.name.trim();

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

    copy.name = copy.name.trim();

    while (names.has(copy.name)) {
      copy.name += "-copy";
    }

    window.__wpfbInvalidated = true;
    setCredentials(credentials.concat(copy));
  };

  const style = useRef(document.createElement("style"));
  useEffect(() => {
    style.current.appendChild(document.createTextNode(CSS));
    document.head.appendChild(style.current);

    return () => {
      document.head.removeChild(style.current);
    };
  }, []);

  if (!schema) return;

  return (
    <PanelBody title={__("Authentication", "forms-bridge")} initialOpen={false}>
      <div style={{ width: "100%" }}>
        <p>{__("HTTP authentication credentials", "forms-bridge")}</p>
        <TabPanel tabs={tabs} className="credentials-tabs-panel">
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
                copy={() => copyCredential(credential.name)}
              />
            );
          }}
        </TabPanel>
      </div>
    </PanelBody>
  );
}
