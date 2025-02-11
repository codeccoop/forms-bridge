// source
import {
  useConfig,
  useSubmitTemplate,
  useTemplate,
} from "../../providers/Templates";
import BackendStep from "./Steps/BackendStep";
import FormStep from "./Steps/FormStep";
import HookStep from "./Steps/HookStep";

const { Button } = wp.components;
const { useMemo, useState, useEffect } = wp.element;
const { __ } = wp.i18n;

const defaultSteps = [
  {
    name: "backend",
    step: ({ fields, data, setData }) => (
      <BackendStep fields={fields} data={data} setData={setData} />
    ),
  },
  {
    name: "form",
    step: ({ fields, data, setData }) => (
      <FormStep fields={fields} data={data} setData={setData} />
    ),
  },
  {
    name: "hook",
    step: ({ fields, data, setData }) => (
      <HookStep fields={fields} data={data} setData={setData} />
    ),
  },
];

function refToGroup(ref) {
  return ref.replace(/^#/, "").replace(/\/.*/, "");
}

function getGroupFields(fields, group) {
  return fields.filter(({ ref }) => new RegExp("^\#" + group).test(ref));
}

export default function TemplateWizard({
  integration,
  steps = [],
  data,
  setData,
  onDone,
}) {
  const sortedSteps = useMemo(
    () =>
      defaultSteps
        .reduce((steps, defaultStep, i) => {
          if (steps.find((step) => step.name === defaultStep.name)) {
            return steps;
          }

          return steps.concat({ order: i * 10, ...defaultStep });
        }, steps)
        .filter(({ step }) => step)
        .sort((a, b) => a.order - b.order),
    [steps]
  );

  const config = useConfig();
  const [template] = useTemplate();
  const submitTemplate = useSubmitTemplate();

  const [step, setStep] = useState(0);

  const fields = useMemo(() => config?.fields || [], [config]);
  const defaults = useMemo(() => {
    return fields.reduce((defaults, field) => {
      if (field.default) {
        const group = refToGroup(field.ref);
        defaults[group] = defaults[group] || {};
        defaults[group][field.name] = field.default;
      }

      return defaults;
    }, {});
  }, [fields]);

  useEffect(() => {
    setData(defaults);
  }, [defaults]);

  const groups = useMemo(() => {
    return fields.reduce((groups, field) => {
      const group = refToGroup(field.ref);

      return {
        ...groups,
        [group]: (groups[group] || []).concat([field]),
      };
    }, {});
  }, [fields]);

  const isValid = useMemo(() => {
    return Object.keys(groups).reduce((isValid, group) => {
      const groupFields = getGroupFields(fields, group);

      return groupFields.reduce(
        (isValid, field) =>
          isValid && (!!data[group]?.[field.name] || !field.required),
        isValid
      );
    }, true);
  }, [data]);

  const isStepDone = useMemo(() => {
    const group = sortedSteps[step].name;
    const groupFields = getGroupFields(fields, group);
    if (!groupFields.length) return true;

    return groupFields.reduce(
      (isValid, field) =>
        isValid && (!!data[group]?.[field.name] || !field.required),
      true
    );
  }, [fields, step, data]);

  const { name: group, step: Step } = sortedSteps[step];

  const submit = () => {
    if (!isValid) return;

    submitTemplate({
      template,
      integration,
      fields: fields.map((field) => {
        const group = refToGroup(field.ref);
        return { ...field, value: data[group][field.name] };
      }),
    }).finally(() => onDone());
  };

  const patchData = (patch) => {
    const groupData = {
      ...(defaults[group] || {}),
      ...(data[group] || {}),
      ...patch,
    };

    setData({ ...data, [group]: groupData });
  };

  const moveStep = (direction) => {
    let newStep = step + direction;
    let group = sortedSteps[newStep].name;
    let groupFields = getGroupFields(fields, group);

    while (
      groupFields.length === 0 &&
      newStep > 0 &&
      newStep < sortedSteps.length - 1
    ) {
      newStep += direction;
      group = sortedSteps[newStep];
      groupFields = getGroupFields(fields, group);
    }

    setStep(newStep);
  };

  if (!config || !config.fields.length) return;

  return (
    <div style={{ minWidth: "575px", minHeight: "125px" }}>
      <hr style={{ margin: "1rem 0" }} />
      <Step
        fields={groups[group] || []}
        data={data[group] || {}}
        setData={patchData}
      />
      <div
        style={{
          padding: "1rem 0 0",
          display: "flex",
          justifyContent: "center",
          alignItems: "center",
          gap: "0.5rem",
        }}
      >
        <Button
          disabled={step <= 0}
          variant="secondary"
          onClick={() => moveStep(-1)}
        >
          {__("Previous", "forms-bridge")}
        </Button>
        {step < sortedSteps.length - 1 ? (
          <Button
            disabled={!isStepDone}
            variant="secondary"
            onClick={() => moveStep(1)}
          >
            {__("Next", "forms-bridge")}
          </Button>
        ) : (
          <Button disabled={!isValid} variant="primary" onClick={submit}>
            {__("Submit", "forms-bridge")}
          </Button>
        )}
      </div>
    </div>
  );
}
