import BackendStep from "./Steps/BackendStep";
import FormStep from "./Steps/FormStep";
import BridgeStep from "./Steps/BridgeStep";
import CredentialStep from "./Steps/CredentialStep";
import { getGroupFields } from "./lib";
import { useSchemas } from "../../../providers/Schemas";

const { useState, useEffect, useMemo } = wp.element;

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
  const { bridge: bridgeSchema } = useSchemas();

  const steps = useMemo(() => {
    return DEFAULT_STEPS.filter((step) =>
      integration === "woo" ? step.name !== "form" : true
    );
  }, [integration, bridgeSchema]);

  const [step, setStep] = useState(0);

  const { name, component: Step } = useMemo(() => steps[step], [steps, step]);

  const stepFields = useMemo(() => {
    return getGroupFields(fields, name);
  }, [step, steps, fields]);

  useEffect(() => {
    if (!fields.length) return;
    if (!stepFields.length) {
      setTimeout(() => move(1));
    }
  }, [fields, stepFields]);

  const done = useMemo(() => {
    if (!stepFields.length) return true;

    return stepFields.reduce((isValid, field) => {
      const value = data[name]?.[field.name]; // || defaults[group]?.[field.name];
      return isValid && (!!value || !field.required);
    }, true);
  }, [stepFields, data, name]);

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
