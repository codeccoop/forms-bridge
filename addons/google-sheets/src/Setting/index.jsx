// vendor
import React from "react";
import {
  Card,
  CardHeader,
  CardBody,
  __experimentalHeading as Heading,
  PanelBody,
  PanelRow,
  ToggleControl,
  TextControl,
  __experimentalSpacer as Spacer,
} from "@wordpress/components";

// source
import FormHooks from "../../../../src/components/FormHooks";
import GSFormHook from "./FormHook";
import useGSApi from "../hooks/useGSApi";

// assets
import logo from "../../assets/logo.png";
import useOath from "../hooks/useOauth";

export default function GoogleSheetSetting() {
  const __ = wp.i18n.__;
  const [{ configured, spreadsheets, form_hooks: hooks }, save] = useGSApi();

  const [oauth, connect] = useOath();

  const update = (field) => save({ spreadsheets, form_hooks: hooks, ...field });

  return (
    <Card size="large" style={{ height: "fit-content" }}>
      <CardHeader>
        <Heading level={3}>{__("Google Sheets", "forms-bridge")}</Heading>
        <img src={"data:image/png;base64," + logo} style={{ width: "21px" }} />
      </CardHeader>
      <CardBody>
        <PanelRow>
          <FormHooks
            hooks={hooks}
            setHooks={(form_hooks) => update({ form_hooks })}
            FormHook={GSFormHook}
          />
        </PanelRow>
        <Spacer paddingY="calc(8px)" />
        <PanelBody
          title={__("Google OAuth", "posts-bridge")}
          initialOpen={!oauth.authorized}
        >
          <TextControl
            __next40pxDefaultSize
            __nextHasNoMarginBottom
            label={__("OAuth client id", "forms-bridge")}
            value={oauth.client_id}
            onChange={(client_id) => update({ client_id })}
          />
          <Spacer paddingY="calc(8px)" />
          <TextControl
            __next40pxDefaultSize
            __nextHasNoMarginBottom
            label={__("OAuth client secret", "forms-bridge")}
            value={oauth.client_secret}
            onChange={(client_secret) => update({ client_secret })}
          />
          <Spacer paddingY="calc(8px)" />
          <ToggleControl
            __next40pxDefaultSize
            __nextHasNoMarginBottom
            label={__("Connect", "forms-bridge")}
            checked={oauth.authorized}
            onChange={() => connect(!oauth.authorized)}
            disabled={!configured}
            help={
              !configured
                ? __(
                    "Disabled until you set a the client id and secret and save settings",
                    "forms-bridge"
                  )
                : ""
            }
          />
        </PanelBody>
      </CardBody>
    </Card>
  );
}
