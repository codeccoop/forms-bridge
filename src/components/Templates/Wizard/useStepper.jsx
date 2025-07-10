import BackendStep from "./Steps/BackendStep";
import FormStep from "./Steps/FormStep";
import BridgeStep from "./Steps/BridgeStep";
import CredentialStep from "./Steps/CredentialStep";
import useAddon from "../../../hooks/useAddon";
import { getGroupFields } from "./lib";

const { useState, useMemo } = wp.element;

const DEFAULT_STEPS = [
  {
    name: "credential",
    component: CredentialStep,
  },
  {
    name: "backend",
    component: BackendStep,
  },
  {
    name: "form",
    component: FormStep,
  },
  {
    name: "bridge",
    component: BridgeStep,
  },
];

export default function useStepper({ fields, integration, data }) {
  const [{ credentials }] = useAddon();

  const steps = useMemo(() => {
    return DEFAULT_STEPS.filter((step) => {
      if (step.name === "credential") {
        return Array.isArray(credentials);
      }

      return step;
    }).filter((step) => (integration === "woo" ? step.name !== "form" : true));
  }, [integration, credentials]);

  const [step, setStep] = useState(0);

  const { name, component: Step } = useMemo(() => steps[step], [steps, step]);

  const done = useMemo(() => {
    const group = steps[step].name;
    const groupFields = getGroupFields(fields, group);
    if (!groupFields.length) return true;

    return groupFields.reduce((isValid, field) => {
      const value = data[group]?.[field.name]; // || defaults[group]?.[field.name];
      return isValid && (!!value || !field.required);
    }, true);
  }, [fields, step, data]);

  const move = (direction) => {
    let newStep = step + direction;
    let group = steps[newStep].name;
    let groupFields = getGroupFields(fields, group);

    while (
      groupFields.length === 0 &&
      newStep > 0 &&
      newStep < steps.length - 1
    ) {
      newStep += direction;
      group = steps[newStep];
      groupFields = getGroupFields(fields, group);
    }

    setStep(newStep);
  };

  return {
    done,
    move,
    step,
    Step,
    name,
    reset: () => setStep(0),
    trailing: step === steps.length - 1,
  };
}
