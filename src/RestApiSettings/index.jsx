// vendor
import React from "react";
import { __ } from "@wordpress/i18n";
import {
  Card,
  CardHeader,
  CardBody,
  __experimentalHeading as Heading,
  PanelRow,
} from "@wordpress/components";

// source
import { useRestApi } from "../providers/Settings";
import FormHooks from "./FormHooks";

export default function RestApiSettings() {
  const __ = wp.i18n.__;
  const [{ form_hooks: hooks }, save] = useRestApi();
  return (
    <Card size="large" style={{ height: "fit-content" }}>
      <CardHeader>
        <Heading level={3}>{__("REST API", "forms-bridge")}</Heading>
      </CardHeader>
      <CardBody>
        <PanelRow>
          <FormHooks
            hooks={hooks}
            setHooks={(form_hooks) => save({ form_hooks })}
          />
        </PanelRow>
      </CardBody>
    </Card>
  );
}
