import { useGeneral } from "../../../../src/providers/Settings";
import TemplateWizard from "../../../../src/components/Templates/Wizard";
import HoldedBridgeStep from "./BridgeStep";
import { useTemplateConfig } from "../../../../src/providers/Templates";
import { debounce } from "../../../../src/lib/utils";

const apiFetch = wp.apiFetch;
const { useState, useEffect, useMemo, useRef } = wp.element;

const STEPS = [
  {
    name: "bridge",
    component: HoldedBridgeStep,
    order: 20,
  },
];

export default function HoldedTemplateWizard({
  integration,
  wired,
  setWired,
  onDone,
}) {
  const [{ backends }] = useGeneral();

  const config = useTemplateConfig();
  const configFields = useMemo(() => config?.fields || [], [config]);
  const customFields = useMemo(() => {
    return configFields
      .filter((field) => field.ref === "#bridge/custom_fields[]")
      .map((field) => field.name);
  }, [configFields]);

  const [data, setData] = useState({});

  if (data.backend && data.backend.base_url !== "https://api.holded.com") {
    setData({
      ...data,
      backend: { ...data.backend, base_url: "https://api.holded.com" },
    });
  }

  const [funnels, setFunnels] = useState([]);
  const [products, setProducts] = useState([]);
  const [services, setServices] = useState([]);

  const backend = useMemo(() => {
    if (!data.backend?.name) return;

    let backend = backends.find(({ name }) => name === data.backend.name);
    if (backend?.headers.find(({ name }) => name === "key")?.value) {
      return backend;
    }

    if (data.backend.name && data.backend.key) {
      return {
        name: data.backend.name,
        base_url: "https://api.holded.com",
        headers: [
          { name: "content-type", value: "application/json" },
          { name: "accept", value: "application/json" },
          { name: "key", value: data.backend.key },
        ],
      };
    }
  }, [data.backend, backends]);

  const fetch = useRef((endpoint, then, backend) => {
    apiFetch({
      path: "forms-bridge/v1/holded/fetch",
      method: "POST",
      data: { backend, endpoint },
    })
      .then(then)
      .catch(() => then([]));
  }).current;

  const fetchFunnels = useRef(
    debounce(
      (backend) => fetch("/api/crm/v1/funnels", setFunnels, backend),
      1e3
    )
  ).current;

  const fetchProducts = useRef(
    debounce(
      (backend) =>
        fetch(
          "/api/invoicing/v1/products",
          (products) => setProducts(products.filter((p) => p.sku)),
          backend
        ),
      1e3
    )
  ).current;

  const fetchServices = useRef(
    debounce(
      (backend) => fetch("/api/invoicing/v1/services", setServices, backend),
      1e3
    )
  ).current;

  useEffect(() => {
    if (!backend || !wired) return;

    customFields.includes("funnelId") && fetchFunnels(backend);
    customFields.includes("sku") && fetchProducts(backend);
    customFields.includes("serviceId") && fetchServices(backend);
  }, [wired, backend, customFields]);

  useEffect(() => {
    setData({
      ...data,
      bridge: {
        ...(data.bridge || {}),
        _funnels: funnels,
        _products: products,
        _services: services,
      },
    });
  }, [funnels, products, services]);

  return (
    <TemplateWizard
      integration={integration}
      data={data}
      setData={setData}
      wired={wired}
      setWired={setWired}
      onDone={onDone}
      steps={STEPS}
    />
  );
}
