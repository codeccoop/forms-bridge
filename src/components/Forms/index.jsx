// source
import useBridges from "../../hooks/useBridges";
import { useForms } from "../../providers/Forms";
import Form from "../Form";

const { TabPanel } = wp.components;
const { useEffect, useMemo, useRef } = wp.element;
const { __ } = wp.i18n;

const CSS = `.forms-tabs-panel>.components-tab-panel__tabs{overflow-x:auto;}
.forms-tabs-panel>.components-tab-panel__tabs>button{flex-shrink:0;}`;

export default function Forms() {
  const [forms] = useForms();
  const [bridges, setBridges] = useBridges();

  const bridgedForms = useMemo(() => {
    return forms.reduce((bridged, form) => {
      const formBridges = bridges.filter(
        (bridge) => bridge.form_id === form._id
      );

      if (!formBridges.length) return bridged;
      return bridged.concat({ ...form, bridges: formBridges });
    }, []);
  }, [forms, bridges]);

  const tabs = useMemo(() => {
    return bridgedForms.map(({ title }, index) => ({
      index,
      name: title,
      title,
    }));
  }, [bridgedForms]);

  const style = useRef(document.createElement("style"));
  useEffect(() => {
    style.current.appendChild(document.createTextNode(CSS));
    document.head.appendChild(style.current);

    return () => {
      document.head.removeChild(style.current);
    };
  }, []);

  useEffect(() => {
    const img = document.querySelector("#forms .addon-logo");
    if (!img) return;
    img.removeAttribute("src");
  }, []);

  const updateBridges = (formBridges) => {
    const order = formBridges.map(({ name }) => name);

    const newBridges = bridges.map((bridge) => {
      const index = order.findIndex((name) => name === bridge.name);
      if (index !== -1) {
        return {
          ...bridge,
          allow_failure: formBridges[index].allow_failure,
          order: index,
        };
      }

      return bridge;
    });

    setBridges(newBridges);
  };

  return (
    <div style={{ width: "100%" }}>
      <h3 style={{ marginTop: 0, fontSize: "13px" }}>
        {__("Bridged forms", "forms-bridge")}
      </h3>
      <TabPanel tabs={tabs} className="forms-tabs-panel">
        {(tab) => {
          const form = bridgedForms[tab.index];
          return <Form data={form} setBridges={updateBridges} />;
        }}
      </TabPanel>
    </div>
  );
}
