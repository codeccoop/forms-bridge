// source
import { useBridges } from "../../hooks/useAddon";
import useBridgeNames from "../../hooks/useBridgeNames";
import ApiSchemaProvider from "../../providers/ApiSchema";
import { useSchemas } from "../../providers/Schemas";
import Bridge from "../Bridge";
import NewBridge from "../Bridge/NewBridge";
import TabTitle from "../TabTitle";
import AddIcon from "../icons/Add";

const { TabPanel } = wp.components;
const { useEffect, useMemo, useRef } = wp.element;
const { __ } = wp.i18n;

const CSS = `.bridges-tabs-panel>.components-tab-panel__tabs{overflow-x:auto;}
.bridges-tabs-panel>.components-tab-panel__tabs>button{flex-shrink:0;}`;

const DEFAULTS = {
  name: "bridge-" + Date.now(),
  backend: "",
  form_id: "",
  enabled: true,
  workflow: [],
  is_valid: true,
  mutations: [[]],
  custom_fields: [],
};

export default function Bridges() {
  const { bridge: schema } = useSchemas();
  const [bridges, setBridges] = useBridges();
  const names = useBridgeNames();

  const tabs = useMemo(() => {
    return Array.from(names)
      .map((name, index) => ({
        index,
        name: String(index),
        title: name,
        icon: <TabTitle name={name} />,
      }))
      .concat([
        {
          index: -1,
          name: "new",
          title: __("Add a bridge", "forms-bridge"),
          icon: (
            <div style={{ marginBottom: "-2px" }}>
              <AddIcon width="15" height="15" />
            </div>
          ),
        },
      ]);
  }, [names]);

  const sanitizeBridgeData = (data) => {
    data = { ...DEFAULTS, ...data };
    data.mutations = data.mutations.slice(0, data.workflow.length + 1);

    for (let i = data.mutations.length; i < data.workflow.length; i++) {
      data.mutations.push([]);
    }

    return data;
  };

  const updateBridge = (index, data) => {
    if (index === -1) index = bridges.length;

    data.name = data.name.trim();

    const newBridges = bridges
      .slice(0, index)
      .concat([sanitizeBridgeData(data)])
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

    copy.name = copy.name.trim();

    while (names.has(copy.name)) {
      copy.name += "-copy";
    }

    window.__wpfbInvalidated = true;
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
      <h3 style={{ marginTop: 0, fontSize: "13px" }}>
        {__("Bridges", "forms-bridge")}
      </h3>
      <TabPanel tabs={tabs} className="bridges-tabs-panel">
        {(tab) => {
          const bridge = bridges[tab.index];

          return (
            <ApiSchemaProvider bridge={bridge}>
              {(!bridge && (
                <NewBridge
                  add={(data) => updateBridge(tab.index, data)}
                  schema={schema}
                  names={names}
                />
              )) || (
                <Bridge
                  data={bridge}
                  schema={schema}
                  remove={removeBridge}
                  update={(data) => updateBridge(tab.index, data)}
                  copy={() => copyBridge(bridge.name)}
                  names={names}
                />
              )}
            </ApiSchemaProvider>
          );
        }}
      </TabPanel>
    </div>
  );
}
