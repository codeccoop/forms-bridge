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
import Forms from "./Forms";

export default function RestApiSettings() {
  const [{ forms }, save] = useRestApi();
  return (
    <Card size="large" style={{ height: "fit-content" }}>
      <CardHeader>
        <Heading level={3}>{__("REST API", "wpct-erp-forms")}</Heading>
      </CardHeader>
      <CardBody>
        <PanelRow>
          <Forms forms={forms} setForms={(forms) => save({ forms })} />
        </PanelRow>
      </CardBody>
    </Card>
  );
}
