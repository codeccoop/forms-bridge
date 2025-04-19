import { useGeneral } from "../../../../src/providers/Settings";
import TemplateWizard from "../../../../src/components/Templates/Wizard";
import BridgeStep from "./BridgeStep";
import { useTemplateConfig } from "../../../../src/providers/Templates";
import { debounce } from "../../../../src/lib/utils";

const apiFetch = wp.apiFetch;
const { useState, useEffect, useMemo, useRef } = wp.element;

const STEPS = [
  {
    name: "bridge",
    component: BridgeStep,
    order: 20,
  },
];

export default function BrevoTemplateWizard({ integration, onDone }) {
  const [{ backends }] = useGeneral();

  const config = useTemplateConfig();
  const configFields = useMemo(() => config?.fields || [], [config]);
  const customFields = useMemo(() => {
    return configFields
      .filter((field) => field.ref === "#bridge/custom_fields[]")
      .map((field) => field.name);
  }, [configFields]);

  const [data, setData] = useState({});

  const [lists, setLists] = useState([]);
  const [products, setProducts] = useState([]);
  const [pipelines, setPipelines] = useState([]);
  const [templates, setTemplates] = useState([]);

  const backend = useMemo(() => {
    if (!data.backend?.name) return;

    let backend = backends.find(({ name }) => name === data.backend.name);
    if (backend?.headers.find(({ name }) => name === "api-key")?.value) {
      return backend;
    }

    if (data.backend.name && data.backend["api-key"]) {
      return {
        name: data.backend.name,
        headers: [{ name: "api-key", value: data.backend["api-key"] }],
      };
    }
  }, [data.backend, backends]);

  const fetch = useRef((module, then, backend) => {
    apiFetch({
      path: `forms-bridge/v1/brevo/${module}`,
      method: "POST",
      data: backend,
    })
      .then(then)
      .catch(() => then([]));
  }).current;

  const fetchLists = useRef(
    debounce((backend) => fetch("lists", setLists, backend), 1e3)
  ).current;

  const fetchProducts = useRef(
    debounce((backend) => fetch("products", setProducts, backend), 1e3)
  ).current;

  const fetchPipelines = useRef(
    debounce((backend) => fetch("pipelines", setPipelines, backend), 1e3)
  ).current;

  const fetchTemplates = useRef(
    debounce((backend) => fetch("templates", setTemplates, backend), 1e3)
  ).current;

  useEffect(() => {
    if (!backend) return;

    (customFields.includes("listIds") ||
      customFields.includes("includeListIds")) &&
      fetchLists(backend);

    (customFields.includes("product") || customFields.includes("products")) &&
      fetchProducts(backend);

    customFields.includes("pipeline") && fetchPipelines(backend);

    customFields.includes("templateId") && fetchTemplates(backend);
  }, [backend, customFields]);

  useEffect(() => {
    setData({
      ...data,
      bridge: {
        ...(data.bridge || {}),
        _lists: lists,
        _products: products,
        _pipelines: pipelines,
        _templates: templates,
      },
    });
  }, [lists, products, pipelines, templates]);

  return (
    <TemplateWizard
      integration={integration}
      data={data}
      setData={setData}
      onDone={onDone}
      steps={STEPS}
    />
  );
}
