// vendor
import React from "react";
import {
  PanelRow,
  TextControl,
  __experimentalSpacer as Spacer,
} from "@wordpress/components";
import { useEffect } from "@wordpress/element";

// source
import { useGeneral } from "../../../providers/Settings";
import Backends from "../../../components/Backends";
import Backend from "../../../components/Backends/Backend";
import Addons from "../../../components/Addons";

export default function GeneralSettings() {
  const __ = wp.i18n.__;

  const [{ notification_receiver, backends, addons }, save] = useGeneral();

  const update = (field) =>
    save({ notification_receiver, backends, addons, ...field });

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
          style={{ width: "220px" }}
          __nextHasNoMarginBottom
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
      <Addons />
    </>
  );
}
