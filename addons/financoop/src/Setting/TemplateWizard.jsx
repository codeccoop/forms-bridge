import { useGeneral } from "../../../../src/providers/Settings";
import TemplateWizard from "../../../../src/components/Templates/Wizard";
import FinancoopBridgeStep from "./BridgeStep";
import { useTemplateConfig } from "../../../../src/providers/Templates";
import { debounce, validateUrl } from "../../../../src/lib/utils";

const apiFetch = wp.apiFetch;
const { useState, useEffect, useMemo, useRef } = wp.element;

const FINANCOOP_HEADERS = ["X-Odoo-Db", "X-Odoo-Username", "X-Odoo-Api-Key"];

const STEPS = [
  {
    name: "bridge",
    component: FinancoopBridgeStep,
    order: 20,
  },
];

function validateBackend(data) {
  return FINANCOOP_HEADERS.reduce((isValid, header) => {
    return isValid && data.headers.find(({ name }) => name === header)?.value;
  }, validateUrl(data.base_url));
}

export default function FinanCoopTemplateWizard({
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

  const [campaigns, setCampaigns] = useState([]);

  const backend = useMemo(() => {
    if (!data.backend?.name) return;

    let backend = backends.find(({ name }) => name === data.backend.name);
    if (backend && validateBackend(backend)) {
      return backend;
    }

    backend = {
      name: data.backend.name,
      base_url: data.backend.base_url,
      headers: FINANCOOP_HEADERS.map((name) => ({
        name,
        value: data.backend[name],
      })),
    };

    if (validateBackend(backend)) {
      return backend;
    }
  }, [data.backend, backends]);

  const fetch = useRef((endpoint, then, backend) => {
    apiFetch({
      path: "forms-bridge/v1/financoop/backend/api/fetch",
      method: "POST",
      data: { backend, endpoint },
    })
      .then(then)
      .catch(() => then([]));
  }).current;

  const fetchCampaigns = useRef(
    debounce((backend) => fetch("/api/campaign", setCampaigns, backend), 1e3)
  ).current;

  useEffect(() => {
    if (!backend || !wired) return;

    customFields.includes("campaign_id") && fetchCampaigns(backend);
  }, [wired, backend, customFields]);

  useEffect(() => {
    setData({
      ...data,
      bridge: {
        ...(data.bridge || {}),
        _campaigns: campaigns,
      },
    });
  }, [campaigns]);

  return (
    <TemplateWizard
      integration={integration}
      data={data}
      setData={setData}
      onDone={onDone}
      wired={wired}
      setWired={setWired}
      steps={STEPS}
    />
  );
}
