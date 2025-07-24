// source
import { useAddons } from "../../hooks/useGeneral";
import GeneralSetting from "../General";
import HttpSetting from "../HttpSetting";
import Addon from "../Addon";
import useTab from "../../hooks/useTab";

const {
  Card,
  CardHeader,
  CardBody,
  TabPanel,
  __experimentalHeading: Heading,
} = wp.components;
const { useEffect, useMemo, useRef } = wp.element;
const { __ } = wp.i18n;

const CSS = `.settings-tabs-panel .components-tab-panel__tabs{overflow-x:auto;}
.settings-tabs-panel .components-tab-panel__tabs>button{flex-shrink:0;}`;

export default function Settings() {
  const [tab, setTab] = useTab();
  const [addons] = useAddons();

  const tabs = useMemo(() => {
    const addonTabs = addons
      .filter(({ enabled }) => enabled)
      .map(({ name, title }) => ({ name, title }));

    return [
      { name: "general", title: __("General", "forms-bridge") },
      { name: "http", title: __("HTTP", "forms-bridge") },
    ].concat(addonTabs);
  }, [addons]);

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
        onSelect={setTab}
        tabs={tabs}
        className="settings-tabs-panel"
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
