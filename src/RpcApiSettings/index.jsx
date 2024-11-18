// vendor
import React from "react";
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
import FormHooks from "./FormHooks";
import { useI18n } from "../providers/I18n";

export default function RpcApiSettings() {
  const __ = useI18n();
  const [{ endpoint, user, password, database, form_hooks: hooks }, save] =
    useRpcApi();

  const update = (field) =>
    save({ endpoint, user, password, database, form_hooks: hooks, ...field });

  return (
    <Card size="large" style={{ height: "fit-content" }}>
      <CardHeader>
        <Heading level={3}>{__("RPC API", "forms-bridge")}</Heading>
      </CardHeader>
      <CardBody>
        <PanelRow>
          <TextControl
            label={__("Endpoint", "forms-bridge")}
            onChange={(endpoint) => update({ endpoint })}
            value={endpoint}
            __nextHasNoMarginBottom
          />
        </PanelRow>
        <PanelRow>
          <TextControl
            label={__("Database", "forms-bridge")}
            onChange={(database) => update({ database })}
            value={database}
            __nextHasNoMarginBottom
          />
        </PanelRow>
        <PanelRow>
          <TextControl
            label={__("User", "forms-bridge")}
            onChange={(user) => update({ user })}
            value={user}
            __nextHasNoMarginBottom
          />
        </PanelRow>
        <PanelRow>
          <TextControl
            label={__("Password", "forms-bridge")}
            onChange={(password) => update({ password })}
            value={password}
            __nextHasNoMarginBottom
          />
        </PanelRow>
        <Spacer paddingY="calc(8px)" />
        <PanelRow>
          <FormHooks
            hooks={hooks}
            setHooks={(form_hooks) => update({ form_hooks })}
          />
        </PanelRow>
      </CardBody>
    </Card>
  );
}
