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
import { useGeneral } from "../providers/Settings";
import Backends from "./Backends";
import { useI18n } from "../providers/I18n";

export default function GeneralSettings() {
  const __ = useI18n();
  const [{ receiver, backends }, save] = useGeneral();

  const update = (field) => save({ receiver, backends, ...field });

  return (
    <Card size="large" style={{ height: "fit-content" }}>
      <CardHeader>
        <Heading level={3}>{__("General", "wpct-erp-forms")}</Heading>
      </CardHeader>
      <CardBody>
        <PanelRow>
          <TextControl
            label={__("Notification receiver", "wpct-erp-forms")}
            onChange={(receiver) => update({ receiver })}
            value={receiver}
            style={{ width: "220px" }}
            __nextHasNoMarginBottom
          />
        </PanelRow>
        <Spacer paddingY="calc(8px)" />
        <PanelRow>
          <Backends
            backends={backends}
            setBackends={(backends) => update({ backends })}
          />
        </PanelRow>
      </CardBody>
    </Card>
  );
}
