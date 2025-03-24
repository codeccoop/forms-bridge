// source
import StoreProvider, { useStoreSubmit } from "../providers/Store";
import SettingsProvider from "../providers/Settings";
import GeneralSettings from "./General";
import Spinner from "../components/Spinner";

const {
  Card,
  CardHeader,
  CardBody,
  TabPanel,
  Notice,
  Button,
  __experimentalHeading: Heading,
  __experimentalSpacer: Spacer,
} = wp.components;
const { useState, useEffect, useRef } = wp.element;
const { __ } = wp.i18n;

function Content({ tab, children }) {
  const content = (() => {
    switch (tab.name) {
      case "general":
        wpfb.emit("api", null);
        return <GeneralSettings />;
      default:
        setTimeout(() => wpfb.emit("api", tab.name));
        return <div className="root" style={{ minHeight: "300px" }}></div>;
    }
  })();

  return (
    <div id={tab.name}>
      <Card size="large" style={{ height: "fit-content" }}>
        <CardHeader>
          <Heading level={3}>{__(tab.title, "forms-bridge")}</Heading>
          <img className="addon-logo" />
        </CardHeader>
        <CardBody>
          {content}
          <Spacer paddingY="calc(16px)" />
          {children}
        </CardBody>
      </Card>
    </div>
  );
}

function SaveButton({ error, loading }) {
  const submit = useStoreSubmit();

  return (
    <Button
      variant="primary"
      onClick={() => submit()}
      style={{ minWidth: "150px", justifyContent: "center" }}
      disabled={loading || error}
      __next40pxDefaultSize
    >
      {__("Save", "forms-bridge")}
    </Button>
  );
}

export default function SettingsPage({ addons }) {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const tabs = [
    {
      name: "general",
      title: __("General", "forms-bridge"),
    },
  ].concat(
    Object.keys(addons).map((addon) => ({
      name: addon,
      title: addons[addon],
    }))
  );

  const initalTab =
    new URLSearchParams(window.location.search).get("tab") || "general";

  const setTab = (tab) => {
    const from = new URLSearchParams(window.location.search);
    const to = new URLSearchParams(from.toString());
    to.set("tab", tab);
    window.history.replaceState(
      { from: `${window.location.pathname}?${from.toString()}` },
      "",
      `${window.location.pathname}?${to.toString()}`
    );
  };

  const onError = useRef((error) => setError(error)).current;
  const onLoading = useRef((loading) => setLoading(loading)).current;

  useEffect(() => {
    wpfb.on("error", onError);
    wpfb.on("loading", onLoading);

    return () => {
      wpfb.off("error", onError);
      wpfb.off("loading", onLoading);
    };
  }, []);

  return (
    <StoreProvider setLoading={setLoading}>
      <Heading level={1}>Forms Bridge</Heading>
      {error && (
        <Notice
          status="error"
          onRemove={() => setError(null)}
          politeness="assertive"
        >
          {error}
        </Notice>
      )}
      <TabPanel initialTabName={initalTab} onSelect={setTab} tabs={tabs}>
        {(tab) => (
          <SettingsProvider handle={["general"]}>
            <Content tab={tab}>
              <SaveButton error={error} loading={loading} />
            </Content>
          </SettingsProvider>
        )}
      </TabPanel>
      <Spacer paddingY="calc(16px)" />
      <Spinner show={loading} />
    </StoreProvider>
  );
}
