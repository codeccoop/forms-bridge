// source
import JobsProvider from "../../providers/Jobs";
import TemplatesProvider from "../../providers/Templates";
import { useAddons } from "../../hooks/useGeneral";
import useAddon from "../../hooks/useAddon";
import Bridges from "../Bridges";
import Credentials from "../Credentials";
import Jobs from "../Jobs";
import useTab from "../../hooks/useTab";
import { useSchemas } from "../../providers/Schemas";

const { PanelRow, __experimentalSpacer: Spacer } = wp.components;
const { useEffect, useMemo } = wp.element;

export default function Addon() {
  const [name] = useTab();
  const { bridge: bridgeSchema } = useSchemas() || {};
  const [{ description }] = useAddon();
  const [addons] = useAddons();

  const logo = useMemo(() => {
    return addons.find((addon) => addon.name === name)?.logo;
  }, [name, addons]);

  useEffect(() => {
    if (!logo) return;

    const img = document.querySelector(`#${name} .addon-logo`);
    if (!img) return;

    img.setAttribute("src", logo);
    img.style.width = "auto";
    img.style.height = "25px";
  }, [name, logo]);

  return (
    <TemplatesProvider>
      <JobsProvider>
        <PanelRow>
          <Bridges />
        </PanelRow>
        <Spacer paddingY="calc(8px)" />
        {bridgeSchema?.properties.credential && <Credentials />}
        <Jobs />
      </JobsProvider>
    </TemplatesProvider>
  );
}
