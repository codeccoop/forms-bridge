import { useGeneral } from "../../../../src/providers/Settings";
import TemplateWizard from "../../../../src/components/Templates/Wizard";
import { useTemplateConfig } from "../../../../src/providers/Templates";
import { debounce, validateUrl } from "../../../../src/lib/utils";
import MailchimpBridgeStep from "./BridgeStep";

const apiFetch = wp.apiFetch;
const { useState, useEffect, useMemo, useRef } = wp.element;

const MAILCHIMP_HEADERS = ["api-key", "datacenter"];

const STEPS = [
  {
    name: "bridge",
    component: MailchimpBridgeStep,
    order: 20,
  },
];

function validateBackend(data) {
  if (!data?.name) return false;

  if (data.base_url && !validateUrl(data.base_url)) {
    return false;
  }

  return MAILCHIMP_HEADERS.reduce((isValid, field) => {
    return isValid && data.headers.find(({ name }) => name === field)?.value;
  }, true);
}

export default function MailchimpTemplateWizard({
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

  if (
    data.backend?.datacenter &&
    data.backend.base_url !==
      `https://${data.backend.datacenter}.api.mailchimp.com`
  ) {
    setData({
      ...data,
      backend: {
        ...data.backend,
        base_url: `https://${data.backend.datacenter}.api.mailchimp.com`,
      },
    });
  }

  const [lists, setLists] = useState([]);

  const backend = useMemo(() => {
    if (!data.backend?.name) return;

    let backend = backends.find(({ name }) => name === data.backend.name);
    if (validateBackend(backend)) {
      return backend;
    }

    backend = {
      name: data.backend.name,
      headers: MAILCHIMP_HEADERS.map((header) => ({
        name: header,
        value: data.backend[header],
      })),
    };

    if (validateBackend(backend)) {
      backend.base_url = `https://${data.backend.datacenter}.api.mailchimp.com`;
      return backend;
    }
  }, [data.backend, backends]);

  const fetch = useRef((endpoint, then, backend) => {
    apiFetch({
      path: "forms-bridge/v1/mailchimp/backend/api/fetch",
      method: "POST",
      data: { backend, endpoint },
    })
      .then(then)
      .catch(() => then([]));
  }).current;

  const fetchLists = useRef(
    debounce(
      (backend) => fetch("/3.0/lists", (data) => setLists(data.lists), backend),
      1e3
    )
  ).current;

  useEffect(() => {
    if (!backend || !wired) return;

    customFields.includes("list_id") && fetchLists(backend);
  }, [wired, backend, customFields]);

  useEffect(() => {
    setData({
      ...data,
      bridge: {
        ...(data.bridge || {}),
        _lists: lists,
      },
    });
  }, [lists]);

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
