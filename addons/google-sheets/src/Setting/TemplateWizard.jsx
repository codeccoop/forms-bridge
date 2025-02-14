import TemplateWizard from "../../../../src/components/Templates/Wizard";
import SpreadsheetStep from "./SpreadsheetStep";

const { useState } = wp.element;

const STEPS = [
  {
    name: "spreadsheet",
    step: ({ fields, data, setData }) => (
      <SpreadsheetStep fields={fields} data={data} setData={setData} />
    ),
    order: 0,
  },
  {
    name: "backend",
    step: null,
    order: 5,
  },
];

export default function GoogleSheetsTemplateWizard({ integration, onDone }) {
  const [data, setData] = useState({});

  return (
    <TemplateWizard
      integration={integration}
      steps={STEPS}
      data={data}
      setData={setData}
      onDone={onDone}
    />
  );
}
