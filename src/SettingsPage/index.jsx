// vendor
import React from "react";
import {
  Card,
  CardHeader,
  CardBody,
  TabPanel,
  __experimentalHeading as Heading,
  Button,
  __experimentalSpacer as Spacer,
} from "@wordpress/components";
import { useState } from "@wordpress/element";

// source
import StoreProvider, { useStoreSubmit } from "../providers/Store";
import SettingsProvider from "../providers/Settings";
import FormsProvider from "../providers/Forms";
import GeneralSettings from "./tabs/General";
import RestApiSettings from "./tabs/RestApi";
import Spinner from "../components/Spinner";

const defaultTabs = [
  {
    name: "general",
    title: "General",
  },
  {
    name: "rest-api",
    title: "REST API",
  },
];

function Content({ tab, children }) {
  const __ = wp.i18n.__;

  const content = (() => {
    switch (tab.name) {
      case "general":
        return <GeneralSettings />;
      case "rest-api":
        return <RestApiSettings />;
      default:
        const root = (
          <div className="root" style={{ minHeight: "300px" }}></div>
        );
        setTimeout(() => wpfb.emit("tab", tab.name));
        return root;
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

function SaveButton({ loading }) {
  const __ = wp.i18n.__;
  const submit = useStoreSubmit();

  const [error, setError] = useState(false);

  const onClick = () => submit().catch(() => setError(true));

  return (
    <Button
      variant={error ? "secondary" : "primary"}
      onClick={onClick}
      style={{ minWidth: "130px", justifyContent: "center" }}
      disabled={loading}
      __next40pxDefaultSize
    >
      {(error && __("Error", "forms-bridge")) || __("Save", "forms-bridge")}
    </Button>
  );
}

export default function SettingsPage({ addons }) {
  const __ = wp.i18n.__;

  const [loading, setLoading] = useState(false);

  const tabs = defaultTabs.concat(
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

  return (
    <StoreProvider setLoading={setLoading}>
      <Heading level={1}>Forms Bridge</Heading>
      <TabPanel
        initialTabName={initalTab}
        onSelect={setTab}
        tabs={tabs.map(({ name, title }) => ({
          name,
          title: __(title, "forms-bridge"),
        }))}
      >
        {(tab) => (
          <FormsProvider>
            <SettingsProvider handle={["general", "rest-api"]}>
              <Spacer />
              <Content tab={tab}>
                <SaveButton loading={loading} />
              </Content>
            </SettingsProvider>
          </FormsProvider>
        )}
      </TabPanel>
      <Spacer show={loading} />
      <Spinner show={loading} />
    </StoreProvider>
  );
}
