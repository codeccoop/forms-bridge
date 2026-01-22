// source
import { useAddons } from "../../hooks/useGeneral";
import GeneralSetting from "../General";
import HttpSetting from "../HttpSetting";
import Addon from "../Addon";
import useTab from "../../hooks/useTab";
import Forms from "../Forms";

const {
  Card,
  CardHeader,
  CardBody,
  TabPanel,
  __experimentalHeading: Heading,
} = wp.components;
const { useEffect, useMemo, useRef } = wp.element;
const { __ } = wp.i18n;

const CSS = `.settings-tabs-panel>.components-tab-panel__tabs{overflow-x:auto;}
.settings-tabs-panel>.components-tab-panel__tabs>button{flex-shrink:0;}`;

export default function Settings() {
  const [tab, setTab] = useTab();
  const [addons] = useAddons();

  const tabRef = useRef(tab);
  const panelRef = useRef();

  const tabs = useMemo(() => {
    const tabs = [
      { name: "general", title: __("General", "forms-bridge") },
      { name: "http", title: __("HTTP", "forms-bridge") },
      { name: "forms", title: __("Forms", "forms-bridge") },
    ];

    const addonTabs = addons
      .filter(({ enabled }) => enabled)
      .map(({ name, title }) => ({ name, title }));

    return tabs.concat(addonTabs);
  }, [addons]);

  const onSelectTab = (tab) => {
    tabRef.current = tab;
    setTab(tab);
  };

  useEffect(() => {
    if (tab === tabRef.current || !panelRef.current) return;

    const index = tabs.findIndex(({ name }) => tab === name);
    const button = panelRef.current.querySelectorAll("button")[index];
    button.click();
  }, [tab, tabs]);

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
      <TabPanel
        initialTabName={tab}
        onSelect={onSelectTab}
        tabs={tabs}
        className="settings-tabs-panel"
        ref={panelRef}
      >
        {(tab) => (
          <div id={tab.name}>
            <Card size="large" style={{ height: "fit-content" }}>
              <CardHeader>
                <Heading level={2} style={{ fontSize: "1.5em" }}>
                  {__(tab.title, "forms-bridge")}
                </Heading>
                <img
                  style={{
                    width: "auto",
                    height: "25px",
                    maxWidth: "90px",
                    objectFit: "contain",
                    objectPosition: "center",
                  }}
                  className="addon-logo"
                />
              </CardHeader>
              <CardBody>
                {tab.name === "general" ? (
                  <GeneralSetting />
                ) : tab.name === "http" ? (
                  <HttpSetting />
                ) : tab.name === "forms" ? (
                  <Forms />
                ) : (
                  <Addon />
                )}
              </CardBody>
            </Card>
          </div>
        )}
      </TabPanel>
    </div>
  );
}
