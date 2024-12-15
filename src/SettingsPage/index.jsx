// vendor
import React from "react";
import {
  TabPanel,
  __experimentalHeading as Heading,
  Button,
  __experimentalSpacer as Spacer,
} from "@wordpress/components";
import { useState, useEffect } from "@wordpress/element";

// source
import StoreProvider, { useStoreSubmit } from "../providers/Store";
import SettingsProvider from "../providers/Settings";
import FormsProvider from "../providers/Forms";
import GeneralSettings from "./tabs/General";
import RestApiSettings from "./tabs/RestApi";

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

function Content({ tab }) {
  switch (tab.name) {
    case "general":
      return <GeneralSettings />;
    case "rest-api":
      return <RestApiSettings />;
    default:
      const root = <div id={tab.name} style={{ minHeight: "400px" }}></div>;
      setTimeout(() => wpfb.emit("tab", tab.name));
      return root;
  }
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

  // redirect to google sheets if comes from oauth redirection
  useEffect(() => {
    const query = new URLSearchParams(window.location.search);
    const oauthRedirection = query.has("code") && query.has("scope");
    if (oauthRedirection) {
      setTimeout(() => {
        document.querySelector("#tab-panel-0-google-sheets-api")?.click();
      }, 500);
    }
  }, []);

  return (
    <StoreProvider setLoading={setLoading}>
      <Heading level={1}>Forms Bridge</Heading>
      <TabPanel
        initialTabName="general"
        tabs={tabs.map(({ name, title }) => ({
          name,
          title: __(title, "forms-bridge"),
        }))}
      >
        {(tab) => (
          <FormsProvider>
            <SettingsProvider handle={["general", "rest-api"]}>
              <Spacer />
              <Content tab={tab} />
            </SettingsProvider>
          </FormsProvider>
        )}
      </TabPanel>
      <SaveButton loading={loading} />
      <Spacer show={loading} />
    </StoreProvider>
  );
}
