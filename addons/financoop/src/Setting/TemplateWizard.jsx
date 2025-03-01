import { useGeneral } from "../../../../src/providers/Settings";
import TemplateWizard from "../../../../src/components/Templates/Wizard";
import BridgeStep from "./BridgeStep";

const apiFetch = wp.apiFetch;
const { useState, useEffect, useMemo } = wp.element;

const STEPS = [
  {
    name: "bridge",
    step: ({ fields, data, setData }) => (
      <BridgeStep fields={fields} data={data} setData={setData} />
    ),
    order: 20,
  },
];

export default function FinanCoopTemplateWizard({ integration, onDone }) {
  const [{ backends }] = useGeneral();
  const [data, setData] = useState({});
  const [campaigns, setCampaigns] = useState([]);

  const backend = useMemo(() => {
    if (!data.backend?.name) return;
    const backend = backends.find(({ name }) => name === data.backend.name);

    if (backend) {
      const headers = backend.headers.reduce((fields, header) => {
        switch (header.name) {
          case "X-Odoo-Db":
            fields.database = header.value;
            break;
          case "X-Odoo-Username":
            fields.username = header.value;
            break;
          case "X-Odoo-Api-Key":
            fields.api_key = header.value;
            break;
        }

        return fields;
      }, {});

      return {
        base_url: backend.base_url,
        ...headers,
      };
    }

    if (data.backend.base_url) {
      return {
        base_url: data.backend.base_url,
        database: data.backend.database,
        username: data.backend.username,
        api_key: data.backend.api_key,
      };
    }
  }, [data.backend, backends]);

  useEffect(() => {
    if (!backend) return;

    apiFetch({
      path: "forms-bridge/v1/financoop/campaigns",
      method: "POST",
      data: backend,
    }).then((campaigns) => {
      setCampaigns(campaigns);
    });
  }, [backend]);

  useEffect(() => {
    setData({ ...data, bridge: { ...(data.bridge || {}), campaigns } });
  }, [campaigns]);

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
