import { useGeneral } from "../../../../src/providers/Settings";
import TemplateWizard from "../../../../src/components/Templates/Wizard";
import DolibarrBridgeStep from "./BridgeStep";
import { useTemplateConfig } from "../../../../src/providers/Templates";
import { debounce, validateUrl } from "../../../../src/lib/utils";

const apiFetch = wp.apiFetch;
const { useState, useMemo, useRef, useEffect } = wp.element;

const STEPS = [
  {
    name: "bridge",
    component: DolibarrBridgeStep,
    order: 20,
  },
];

function validateBackend(data) {
  if (!data?.name) return;

  return (
    validateUrl(data.base_url) &&
    data.headers.find(({ name }) => name === "DOLAPIKEY")?.value
  );
}

export default function DolibarrTemplateWizard({
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
  const [products, setProducts] = useState([]);

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
        {
          name: "DOLAPIKEY",
          value: data.backend.DOLAPIKEY,
        },
      ],
    };

    if (validateBackend(backend)) {
      return backend;
    }
  });

  const fetch = useRef((endpoint, then, backend) => {
    apiFetch({
      path: `forms-bridge/v1/dolibarr/backend/api/fetch`,
      method: "POST",
      data: { backend, endpoint },
    })
      .then(then)
      .catch(() => then([]));
  }).current;

  const fetchUsers = useRef(
    debounce((backend) => fetch("/api/index.php/users", setUsers, backend), 1e3)
  ).current;

  const fetchProducts = useRef(
    debounce(
      (backend) => fetch("/api/index.php/products", setProducts, backend),
      1e3
    )
  ).current;

  useEffect(() => {
    if (!backend || !wired) return;

    customFields.includes("userownerid") && fetchUsers(backend);
    customFields.includes("fk_product") && fetchProducts(backend);
  }, [wired, backend, customFields]);

  useEffect(() => {
    setData({
      ...data,
      bridge: {
        ...(data.bridge || {}),
        _users: users,
        _products: products,
      },
    });
  }, [users, products]);

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
