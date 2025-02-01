// vendor
import React from "react";
import {
  PanelBody,
  PanelRow,
  TextControl,
  __experimentalSpacer as Spacer,
  ButtonGroup,
} from "@wordpress/components";
import { useEffect } from "@wordpress/element";

// source
import { useGeneral } from "../../../providers/Settings";
import Backends from "../../../components/Backends";
import Backend from "../../../components/Backends/Backend";
import Integrations from "../../../components/Integrations";
import Addons from "../../../components/Addons";
import Logger from "./Logger";
import Exporter from "./Exporter";

export default function GeneralSettings() {
  const __ = wp.i18n.__;

  const [{ notification_receiver, backends, debug, ...general }, save] =
    useGeneral();

  const update = (field) =>
    save({ notification_receiver, backends, debug, ...general, ...field });

  useEffect(() => {
    const img = document.querySelector("#general .addon-logo");
    if (!img) return;
    img.removeAttribute("src");
  }, []);

  return (
    <>
      <PanelRow>
        <TextControl
          label={__("Notification receiver", "forms-bridge")}
          onChange={(notification_receiver) =>
            update({ notification_receiver })
          }
          value={notification_receiver || ""}
          style={{ width: "300px" }}
          __nextHasNoMarginBottom
          __next40pxDefaultSize
        />
      </PanelRow>
      <Spacer paddingY="calc(8px)" />
      <PanelRow>
        <Backends
          backends={backends}
          setBackends={(backends) => update({ backends })}
          Backend={Backend}
        />
      </PanelRow>
      <Spacer paddingY="calc(8px)" />
      <Integrations />
      <Addons />
      <PanelBody title={__("Debug", "forms-bridge")} initialOpen={!!debug}>
        <Logger />
      </PanelBody>
      <PanelBody
        title={__("Import / Export", "forms-bridge")}
        initialOpen={false}
      >
        <Exporter />
      </PanelBody>
    </>
  );
}
