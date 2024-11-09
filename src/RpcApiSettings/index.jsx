// vendor
import React from "react";
import { __ } from "@wordpress/i18n";
import {
  Card,
  CardHeader,
  CardBody,
  __experimentalHeading as Heading,
  PanelRow,
  TextControl,
  __experimentalSpacer as Spacer,
} from "@wordpress/components";

// source
import { useRpcApi } from "../providers/Settings";
import Forms from "./Forms";

export default function RpcApiSettings() {
  const [{ endpoint, user, password, database, forms }, save] = useRpcApi();

  const update = (field) =>
    save({ endpoint, user, password, database, forms, ...field });

  return (
    <Card size="large" style={{ height: "fit-content" }}>
      <CardHeader>
        <Heading level={3}>{__("RPC API", "wpct-erp-forms")}</Heading>
      </CardHeader>
      <CardBody>
        <PanelRow>
          <TextControl
            label={__("Endpoint", "wpct-erp-forms")}
            onChange={(endpoint) => update({ endpoint })}
            value={endpoint}
            __nextHasNoMarginBottom
          />
        </PanelRow>
        <PanelRow>
          <TextControl
            label={__("Database", "wpct-erp-forms")}
            onChange={(database) => update({ database })}
            value={database}
            __nextHasNoMarginBottom
          />
        </PanelRow>
        <PanelRow>
          <TextControl
            label={__("User", "wpct-erp-forms")}
            onChange={(user) => update({ user })}
            value={user}
            __nextHasNoMarginBottom
          />
        </PanelRow>
        <PanelRow>
          <TextControl
            label={__("Password", "wpct-erp-forms")}
            onChange={(password) => update({ password })}
            value={password}
            __nextHasNoMarginBottom
          />
        </PanelRow>
        <Spacer paddingY="calc(8px)" />
        <PanelRow>
          <Forms forms={forms} setForms={(forms) => update({ forms })} />
        </PanelRow>
      </CardBody>
    </Card>
  );
}
