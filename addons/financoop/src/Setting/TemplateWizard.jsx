import { useGeneral } from "../../../../src/providers/Settings";
import TemplateWizard from "../../../../src/components/Templates/Wizard";
import BridgeStep from "./BridgeStep";

const apiFetch = wp.apiFetch;
const { useState, useEffect, useMemo, useRef } = wp.element;

const FINANCOOP_HEADERS = ["X-Odoo-Db", "X-Odoo-Username", "X-Odoo-Api-Key"];

const STEPS = [
  {
    name: "bridge",
    step: ({ fields, data, setData }) => (
      <BridgeStep fields={fields} data={data} setData={setData} />
    ),
    order: 20,
  },
];

function debounce(fn, ms = 500) {
  let timeout;

  return (...args) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => fn(...args), ms);
  };
}

function validateBackendData(data) {
  return FINANCOOP_HEADERS.reduce(
    (isValid, field) => {
      return isValid && data.headers[field];
    },
    /https?\:\/\/[^\/]+\.\w\w+/.test(data.base_url)
  );
}

export default function FinanCoopTemplateWizard({ integration, onDone }) {
  const [{ backends }] = useGeneral();
  const [data, setData] = useState({});
  const [campaigns, setCampaigns] = useState([]);

  const backendData = useMemo(() => {
    if (!data.backend?.name) return;
    const backend = backends.find(({ name }) => name === data.backend.name);

    if (backend && validateBackendData(backend)) {
      return backend;
    }

    const backendData = {
      name: data.backend.name,
      base_url: data.backend.base_url,
      headers: FINANCOOP_HEADERS.reduce(
        (headers, name) => ({
          ...headers,
          [name]: data.backend[name],
        }),
        {}
      ),
    };

    if (validateBackendData(backendData)) {
      return backendData;
    }
  }, [data.backend, backends]);

  const fetchCampaigns = useRef(
    debounce(
      (data) =>
        apiFetch({
          path: "forms-bridge/v1/financoop/campaigns",
          method: "POST",
          data,
        })
          .then(setCampaigns)
          .catch(() => setCampaigns([])),
      300
    )
  ).current;

  useEffect(() => {
    if (!backendData) return;
    fetchCampaigns(backendData);
  }, [backendData]);

  useEffect(
    () => setData({ ...data, bridge: { ...(data.bridge || {}), campaigns } }),
    [campaigns]
  );

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
