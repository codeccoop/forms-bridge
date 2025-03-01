// source
import CopyIcon from "../CopyIcon";

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

export default function Bridges({ bridges, setBridges, Bridge }) {
  const [currentTab, setCurrentTab] = useState(String(bridges.length ? 0 : -1));
  const [tabFocus, setTabFocus] = useState(null);
  const tabs = bridges
    .map(({ backend, form_id, name, mappers, ...customFields }, i) => ({
      ...customFields,
      name: String(i),
      title: name,
      backend,
      form_id,
      mappers,
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
        name: "-1",
        title: __("Add bridge", "forms-bridge"),
      },
    ]);

  const bridgesCount = useRef(bridges.length);
  useEffect(() => {
    if (bridges.length > bridgesCount.current) {
      setCurrentTab(String(bridges.length - 1));
    } else if (bridges.length < bridgesCount.current) {
      setCurrentTab(String(currentTab - 1));
    }

    return () => {
      bridgesCount.current = bridges.length;
    };
  }, [bridges]);

  const updateBridge = (index, data) => {
    if (index === -1) index = bridges.length;

    const newBridges = bridges
      .slice(0, index)
      .concat([data])
      .concat(bridges.slice(index + 1, bridges.length));

    newBridges.forEach((bridge) => {
      delete bridge.title;
      delete bridge.icon;
    });

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
      mappers: JSON.parse(JSON.stringify(bridge.mappers || [])),
    };

    let isUnique = false;
    while (!isUnique) {
      copy.name += "-copy";
      isUnique = bridges.find((h) => h.name === copy.name) === undefined;
    }

    setBridges(bridges.concat(copy));
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
        {__("Bridges", "forms-bridge")}
      </label>
      <TabPanel
        tabs={tabs}
        onSelect={setCurrentTab}
        initialTabName={currentTab}
      >
        {(bridge) => {
          bridge.name = bridge.name >= 0 ? bridges[+bridge.name].name : "add";
          return (
            <Bridge
              data={bridge}
              remove={removeBridge}
              update={(data) =>
                updateBridge(
                  bridges.findIndex(({ name }) => name === bridge.name),
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
