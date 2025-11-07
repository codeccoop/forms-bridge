// source
import useBackendNames from "../../hooks/useBackendNames";
import Backend from "../Backend";
import NewBackend from "../Backend/NewBackend";
import TabTitle from "../TabTitle";
import AddIcon from "../icons/Add";

const { useRef, useEffect } = wp.element;
const { TabPanel } = wp.components;
const { __ } = wp.i18n;

const CSS = `.backends-tabs-panel .components-tab-panel__tabs{overflow-x:auto;}
.backends-tabs-panel .components-tab-panel__tabs>button{flex-shrink:0;}`;

export default function Backends({ backends, setBackends }) {
  const names = useBackendNames();

  const tabs = backends
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
        title: __("Add a backend", "forms-bridge"),
        icon: (
          <div style={{ marginBottom: "-2px" }}>
            <AddIcon width="15" height="15" />
          </div>
        ),
      },
    ]);

  const updateBackend = (index, data) => {
    if (index === -1) index = backends.length;

    if (!data.headers?.length) {
      data.headers = [{ name: "Content-Type", value: "application/json" }];
    }

    data.name = data.name.trim();
    data.base_url = data.base_url.trim();

    const newBackends = backends
      .slice(0, index)
      .concat([data])
      .concat(backends.slice(index + 1, backends.length));

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

    copy.name = copy.name.trim();
    copy.base_url = copy.base_url.trim();

    while (names.has(copy.name)) {
      copy.name += "-copy";
    }

    window.__wpfbInvalidated = true;
    setBackends(backends.concat(copy));
  };

  const style = useRef(document.createElement("style"));
  useEffect(() => {
    style.current.appendChild(document.createTextNode(CSS));
    document.head.appendChild(style.current);

    return () => {
      document.head.removeChild(style.current);
    };
  }, []);

  return (
    <div style={{ width: "100%" }}>
      <h3 style={{ marginTop: 0, fontSize: "13px" }}>
        {__("Backends", "forms-bridge")}
      </h3>
      <TabPanel tabs={tabs} className="backends-tabs-panel">
        {(tab) => {
          const backend = backends[tab.index];

          if (!backend) {
            return (
              <NewBackend add={(data) => updateBackend(tab.index, data)} />
            );
          }

          return (
            <Backend
              data={backend}
              remove={removeBackend}
              update={(newBackend) => updateBackend(tab.index, newBackend)}
              copy={() => copyBackend(backend.name)}
            />
          );
        }}
      </TabPanel>
    </div>
  );
}
