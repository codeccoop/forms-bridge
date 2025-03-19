import { useGeneral } from "../../../../src/providers/Settings";
import TemplateWizard from "../../../../src/components/Templates/Wizard";
import BackendStep from "../../../../src/components/Templates/Steps/BackendStep";
import useCredentialNames from "../hooks/useCredentialNames";
import CredentialStep from "./CredentialStep";

const { useState, useMemo, useEffect, useRef } = wp.element;

export default function ZohoTemplateWizard({ integration, onDone }) {
  const [{ backends }] = useGeneral();
  const credentialNames = useCredentialNames();
  const [data, setData] = useState({});

  const defaultSteps = useRef([
    {
      name: "credential",
      step: ({ fields, data, setData }) => (
        <CredentialStep fields={fields} data={data} setData={setData} />
      ),
      order: 0,
    },
    {
      name: "backend",
      step: ({ fields, data, setData }) => (
        <BackendStep fields={fields} data={data} setData={setData} />
      ),
      order: 5,
    },
  ]).current;

  const steps = useMemo(
    () =>
      defaultSteps.map(({ name, step, order }) => {
        if (
          name === "backend" &&
          data.credential?.name &&
          credentialNames.has(data.credential.name)
        ) {
          step = null;
        }

        return { name, step, order };
      }),
    [credentialNames, data.credential?.name]
  );

  useEffect(() => {
    if (data.credential?.name && credentialNames.has(data.credential.name)) {
      const backend = backends.find(
        ({ name }) => name === data.credential.backend
      );
      data.backend.name = backend.name;
      data.backend.base_url = backend.base_url;
    }
  }, [data.credential?.name]);

  return (
    <TemplateWizard
      integration={integration}
      steps={steps}
      data={data}
      setData={setData}
      onDone={onDone}
    />
  );
}
