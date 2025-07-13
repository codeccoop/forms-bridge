// source
import { useBridges } from "../../hooks/useAddon";
import ApiSchemaProvider from "../../providers/ApiSchema";
import { useSchemas } from "../../providers/Schemas";
import Bridge from "../Bridge";
import NewBridge from "../Bridge/NewBridge";
import CopyIcon from "../CopyIcon";
import Spinner from "../Spinner";

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

const CSS = `.bridges-tabs-panel .components-tab-panel__tabs{overflow-x:auto;}
.bridges-tabs-panel .components-tab-panel__tabs>button{flex-shrink:0;}`;

const DEFAULTS = {
  enabled: true,
  workflow: [],
  is_valid: true,
  mutations: [],
  custom_fields: [],
};

export default function Bridges() {
  const { bridge: schema } = useSchemas();
  const [bridges, setBridges] = useBridges();

  const [tabFocus, setTabFocus] = useState(null);

  const tabs = bridges
    .map(({ name }, index) => ({
      index,
      name: String(index),
      title: name,
      icon: (
        <TabTitle
          name={name}
          focus={tabFocus === name}
          setFocus={(value) => setTabFocus(value ? name : null)}
          copy={() => copyBridge(name)}
        />
      ),
    }))
    .concat([
      {
        index: -1,
        name: "new",
        title: __("Add bridge", "forms-bridge"),
      },
    ]);

  const updateBridge = (index, data) => {
    if (index === -1) index = bridges.length;

    const newBridges = bridges
      .slice(0, index)
      .concat([{ ...DEFAULTS, ...data }])
      .concat(bridges.slice(index + 1, bridges.length));

    setBridges(newBridges);
  };

  const removeBridge = ({ name }) => {
    const index = bridges.findIndex((b) => b.name === name);
    const newBridges = bridges.slice(0, index).concat(bridges.slice(index + 1));
    setBridges(newBridges);
  };

  const copyBridge = (name) => {
    const i = bridges.findIndex((b) => b.name === name);
    const bridge = bridges[i];

    const copy = {
      ...bridge,
      workflow: bridge.workflow.map((job) => job),
      mutations: JSON.parse(
        JSON.stringify(bridge.mutations || bridge.workflow.map(() => []))
      ),
      custom_fields: JSON.parse(JSON.stringify(bridge.custom_fields || [])),
    };

    while (bridgeNames.has(copy.name)) {
      copy.name += "-copy";
    }

    setBridges(bridges.concat(copy));
  };

  const style = useRef(document.createElement("style"));
  useEffect(() => {
    style.current.appendChild(document.createTextNode(CSS));
    document.head.appendChild(style.current);

    return () => {
      document.head.removeChild(style.current);
    };
  }, []);

  if (!schema) return null;

  return (
    <div style={{ width: "100%" }}>
      <TabPanel tabs={tabs} className="bridges-tabs-panel">
        {(tab) => {
          const bridge = bridges[tab.index];

          return (
            <ApiSchemaProvider bridge={bridge}>
              {(!bridge && (
                <NewBridge
                  add={(data) => updateBridge(tab.index, data)}
                  schema={schema}
                />
              )) || (
                <Bridge
                  data={bridge}
                  schema={schema}
                  remove={removeBridge}
                  update={(data) => updateBridge(tab.index, data)}
                />
              )}
            </ApiSchemaProvider>
          );
        }}
      </TabPanel>
    </div>
  );
}
