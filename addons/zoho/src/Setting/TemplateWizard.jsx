import TemplateWizard from "../../../../src/components/Templates/Wizard";
import BackendStep from "../../../../src/components/Templates/Steps/BackendStep";
import CredentialStep from "./CredentialStep";

const DEFAULT_STEPS = [
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
];

export default function ZohoTemplateWizard({
  integration,
  onDone,
  data,
  setData,
}) {
  return (
    <TemplateWizard
      integration={integration}
      steps={DEFAULT_STEPS}
      data={data}
      setData={setData}
      onDone={onDone}
    />
  );
}
