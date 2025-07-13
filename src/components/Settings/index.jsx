// source
import { useAddons } from "../../hooks/useGeneral";
import GeneralSetting from "../General";
import Addon from "../Addon";
import useTab from "../../hooks/useTab";

const {
  Card,
  CardHeader,
  CardBody,
  TabPanel,
  __experimentalHeading: Heading,
} = wp.components;
const { useMemo } = wp.element;
const { __ } = wp.i18n;

export default function Settings() {
  const [tab, setTab] = useTab();
  const [addons] = useAddons();

  const tabs = useMemo(() => {
    const addonTabs = addons
      .filter(({ enabled }) => enabled)
      .map(({ name, title }) => ({ name, title }));

    return [{ name: "general", title: __("General", "forms-bridge") }].concat(
      addonTabs
    );
  }, [addons]);

  return (
    <TabPanel initialTabName={tab} onSelect={setTab} tabs={tabs}>
      {(tab) => (
        <div id={tab.name}>
          <Card size="large" style={{ height: "fit-content" }}>
            <CardHeader>
              <Heading level={3}>{__(tab.title, "forms-bridge")}</Heading>
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
              {(tab.name === "general" && <GeneralSetting />) || <Addon />}
            </CardBody>
          </Card>
        </div>
      )}
    </TabPanel>
  );
}
