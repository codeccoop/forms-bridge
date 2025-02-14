import TemplateWizard from "../../../../src/components/Templates/Wizard";

const { useState } = wp.element;

export default function FinanCoopTemplateWizard({ integration, onDone }) {
  const [data, setData] = useState({});

  return (
    <TemplateWizard
      integration={integration}
      data={data}
      setData={setData}
      onDone={onDone}
    />
  );
}
