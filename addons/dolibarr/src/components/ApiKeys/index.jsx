// source
import ApiKey from "./ApiKey";
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

export default function ApiKeys({ apiKeys, setApiKeys }) {
  const [currentTab, setCurrentTab] = useState(String(apiKeys.length ? 0 : -1));
  const [tabFocus, setTabFocus] = useState(null);
  const tabs = apiKeys
    .map(({ name, key, backend }, i) => ({
      name: String(i),
      title: name,
      backend,
      key,
      icon: (
        <TabTitle
          name={name}
          focus={tabFocus === name}
          setFocus={(value) => setTabFocus(value ? name : null)}
          copy={() => copyApiKey(name)}
        />
      ),
    }))
    .concat([
      {
        name: "-1",
        title: __("Add API key", "forms-bridge"),
      },
    ]);

  const keyCount = useRef(apiKeys.length);
  useEffect(() => {
    if (apiKeys.length > keyCount.current) {
      setCurrentTab(String(apiKeys.length - 1));
    } else if (apiKeys.length < keyCount.current) {
      setCurrentTab(String(currentTab - 1));
    }

    return () => {
      keyCount.current = apiKeys.length;
    };
  }, [apiKeys]);

  const updateApiKeys = (index, data) => {
    if (index === -1) index = apiKeys.length;
    const newApiKeys = apiKeys
      .slice(0, index)
      .concat([data])
      .concat(apiKeys.slice(index + 1, apiKeys.length));

    newApiKeys.forEach((apiKey) => {
      delete apiKey.title;
      delete apiKey.icon;
    });
    setApiKeys(newApiKeys);
  };

  const removeApiKey = ({ name }) => {
    const index = apiKeys.findIndex((key) => key.name === name);
    const newApiKeys = apiKeys.slice(0, index).concat(apiKeys.slice(index + 1));
    setApiKeys(newApiKeys);
  };

  const copyApiKey = (name) => {
    const i = apiKeys.findIndex((key) => key.name === name);
    const apiKey = apiKeys[i];
    const copy = { ...apiKey };

    let isUnique = false;
    if (!isUnique) {
      copy.name += "-copy";
      isUnique = apiKeys.find((key) => key.name === copy.name) === undefined;
    }

    setApiKeys(apiKeys.concat(copy));
  };

  return (
    <div style={{ width: "100%" }}>
      <TabPanel
        tabs={tabs}
        onSelect={setCurrentTab}
        initialTabName={currentTab}
      >
        {(apiKey) => {
          apiKey.name = apiKey.name >= 0 ? apiKeys[+apiKey.name].name : "add";
          return (
            <ApiKey
              data={apiKey}
              remove={removeApiKey}
              update={(data) =>
                updateApiKeys(
                  apiKeys.findIndex(({ name }) => name === apiKey.name),
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
