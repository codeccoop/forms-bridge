import { useGeneral } from "../../../../src/providers/Settings";
import { useTemplateConfig } from "../../../../src/providers/Templates";
import TemplateWizard from "../../../../src/components/Templates/Wizard";
import BackendStep from "../../../../src/components/Templates/Steps/BackendStep";
import BiginCredentialStep from "./CredentialStep";
import BiginBridgeStep from "./BridgeStep";
import { debounce, validateUrl } from "../../../../src/lib/utils";

const apiFetch = wp.apiFetch;
const { useState, useMemo, useEffect, useRef } = wp.element;

const STEPS = [
  {
    name: "credential",
    component: BiginCredentialStep,
    order: 0,
  },
  {
    name: "backend",
    component: BackendStep,
    order: 5,
  },
  {
    name: "bridge",
    component: BiginBridgeStep,
    order: 20,
  },
];

function validateBackend(data) {
  if (!data?.name) return false;
  if (!validateUrl(data.base_url)) return false;
  if (!/www\.zohoapis\./.test(data.base_url)) return false;
  return true;
}

export default function BiginTemplateWizard({
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

  const [users, setUsers] = useState([]);

  const backend = useMemo(() => {
    if (!data.backend?.name) return;

    let backend = backends.find(({ name }) => name === data.backend.name);
    if (validateBackend(backend)) {
      return backend;
    }

    backend = {
      name: data.backend.name,
      base_url: data.backend.base_url,
      headers: [
        {
          name: "Content-Type",
          value: "application/json",
        },
        {
          name: "Accept",
          value: "application/json",
        },
      ],
    };

    if (validateBackend(backend)) {
      return backend;
    }
  }, [data.backend, backends]);

  const fetch = useRef((endpoint, then, backend, credential) => {
    apiFetch({
      path: "forms-bridge/v1/bigin/backend/api/fetch",
      method: "POST",
      data: { backend, endpoint, credential },
    })
      .then(then)
      .catch(() => then([]));
  }).current;

  const fetchUsers = useRef(
    debounce((backend, credential) => {
      fetch(
        "/bigin/v2/users",
        (data) => setUsers(data.users),
        backend,
        credential
      );
    }, 1e3)
  ).current;

  useEffect(() => {
    if (!backend || !wired) return;

    customFields.includes("Owner.id") && fetchUsers(backend, data.credential);
  }, [wired, backend, customFields, data.credential]);

  useEffect(() => {
    setData({
      ...data,
      bridge: {
        ...(data.bridge || {}),
        _users: users,
      },
    });
  }, [users]);

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
