// vendor
import React from "react";
import {
  TabPanel,
  __experimentalHeading as Heading,
  Button,
  __experimentalSpacer as Spacer,
} from "@wordpress/components";
import { useState } from "@wordpress/element";

// source
import SettingsProvider, { useSubmitSettings } from "../providers/Settings";
import FormsProvider from "../providers/Forms";
import GeneralSettings from "../GeneralSettings";
import RestApiSettings from "../RestApiSettings";
import RpcApiSettings from "../RpcApiSettings";

const tabs = [
  {
    name: "general",
    title: "General",
  },
  {
    name: "rest-api",
    title: "REST API",
  },
  {
    name: "rpc-api",
    title: "Odoo JSON-RPC",
  },
];

function Content({ tab }) {
  switch (tab.name) {
    case "rest-api":
      return <RestApiSettings />;
    case "rpc-api":
      return <RpcApiSettings />;
    default:
      return <GeneralSettings />;
  }
}

function SaveButton() {
  const __ = wp.i18n.__;
  const submit = useSubmitSettings();

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(false);

  const onClick = () => {
    setLoading(true);
    submit()
      .then(() => setLoading(false))
      .catch(() => setError(true));
  };

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

export default function SettingsPage() {
  const __ = wp.i18n.__;
  return (
    <SettingsProvider>
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
            <Spacer />
            <Content tab={tab} />
          </FormsProvider>
        )}
      </TabPanel>
      <Spacer />
      <SaveButton />
    </SettingsProvider>
  );
}
