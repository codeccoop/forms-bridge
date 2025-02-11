import { useGeneral } from "../../../../src/providers/Settings";
import TemplateWizard from "../../../../src/components/Templates/Wizard";
import BackendStep from "../../../../src/components/Templates/Steps/BackendStep";
import useDatabaseNames from "../hooks/useDatabaseNames";
import DatabaseStep from "./DatabaseStep";

const { useState, useMemo, useEffect, useRef } = wp.element;

export default function OdooTemplateWizard({ integration, onDone }) {
  const [{ backends }] = useGeneral();
  const databaseNames = useDatabaseNames();
  const [data, setData] = useState({});

  const defaultSteps = useRef([
    {
      name: "database",
      step: ({ fields, data, setData }) => (
        <DatabaseStep fields={fields} data={data} setData={setData} />
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
          data.database?.name &&
          databaseNames.has(data.database.name)
        ) {
          step = null;
        }

        return { name, step, order };
      }),
    [databaseNames, data.database?.name]
  );

  useEffect(() => {
    if (data.database?.name && databaseNames.has(data.database.name)) {
      const backend = backends.find(
        ({ name }) => name === data.database.backend
      );
      data.backend.name = backend.name;
      data.backend.base_url = backend.base_url;
    }
  }, [data.database?.name]);

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
