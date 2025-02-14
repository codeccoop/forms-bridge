// source
import { useGeneral } from "../../providers/Settings";
import Backends from "../../components/Backends";
import Backend from "../../components/Backends/Backend";
import Integrations from "../../components/Integrations";
import Addons from "../../components/Addons";
import Logger from "./Logger";
import Exporter from "./Exporter";

const {
  PanelBody,
  PanelRow,
  TextControl,
  __experimentalSpacer: Spacer,
} = wp.components;
const { useEffect } = wp.element;
const { __ } = wp.i18n;

export default function GeneralSettings() {
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
      <PanelBody
        title={__("Backends", "forms-bridge")}
        initialOpen={backends.length === 0}
      >
        <Spacer paddingY="calc(8px)" />
        <PanelRow>
          <Backends
            backends={backends}
            setBackends={(backends) => update({ backends })}
            Backend={Backend}
          />
        </PanelRow>
      </PanelBody>
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
