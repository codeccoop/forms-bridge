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

export default function Backends({ backends, setBackends, Backend }) {
  const [currentTab, setCurrentTab] = useState(
    String(backends.length ? 0 : -1)
  );
  const [tabFocus, setTabFocus] = useState(null);
  const tabs = backends
    .map(({ name, base_url, headers }, i) => ({
      name: String(i),
      title: name,
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
