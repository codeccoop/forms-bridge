import { useGeneral } from "../../../../src/providers/Settings";
import { useTemplateConfig } from "../../../../src/providers/Templates";
import TemplateWizard from "../../../../src/components/Templates/Wizard";
import BackendStep from "../../../../src/components/Templates/Steps/BackendStep";
import CredentialStep from "./CredentialStep";
import BridgeStep from "./BridgeStep";
import { debounce, validateUrl } from "../../../../src/lib/utils";

const apiFetch = wp.apiFetch;
const { useState, useMemo, useEffect, useRef } = wp.element;

const STEPS = [
  {
    name: "credential",
    component: CredentialStep,
    order: 0,
  },
  {
    name: "backend",
    component: BackendStep,
    order: 5,
  },
  {
    name: "bridge",
    component: BridgeStep,
    order: 20,
  },
];

function validateBackend(data) {
  if (!data?.name) return false;

  return ["Content-Type", "Accept"].reduce((isValid, header) => {
    return isValid && data.headers.find(({ name }) => name === header);
  }, validateUrl(data.base_url));
}

export default function OdooTemplateWizard({
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
  const [tags, setTags] = useState([]);
  const [teams, setTeams] = useState([]);
  const [lists, setLists] = useState([]);

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

  const fetch = useRef((model, then, credential, backend) => {
    apiFetch({
      path: `forms-bridge/v1/odoo/fetch`,
      method: "POST",
      data: { backend, credential, endpoint: model },
    })
      .then(then)
      .catch(() => then([]));
  }).current;

  const fetchUsers = useRef(
    debounce((...args) => fetch("res.users", setUsers, ...args), 1e3)
  ).current;

  const fetchProducts = useRef(
    debounce((...args) => fetch("product.product", setProducts, ...args), 1e3)
  ).current;

  const fetchTags = useRef(
    debounce((...args) => fetch("crm.tag", setTags, ...args), 1e3)
  ).current;

  const fetchTeams = useRef(
    debounce((...args) => fetch("crm.team", setTeams, ...args), 1e3)
  ).current;

  const fetchLists = useRef(
    debounce((...args) => fetch("mailing.list", setLists, ...args), 1e3)
  ).current;

  useEffect(() => {
    if (!backend || !wired) return;

    customFields.includes("user_id") && fetchUsers(data.credential, backend);

    customFields.includes("product_id") &&
      fetchProducts(data.credential, backend);

    customFields.includes("tag_ids") && fetchTags(data.credential, backend);

    customFields.includes("team_id") && fetchTeams(data.credential, backend);

    customFields.includes("list_ids") && fetchLists(data.credential, backend);
  }, [wired, backend, customFields, data.credential]);

  useEffect(() => {
    setData({
      ...data,
      bridge: {
        ...(data.bridge || {}),
        _users: users,
        _products: products,
        _tags: tags,
        _teams: teams,
        _lists: lists,
      },
    });
  }, [users, products, tags, teams, lists]);

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
