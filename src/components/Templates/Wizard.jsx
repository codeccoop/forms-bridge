// source
import {
  useTemplateConfig,
  useSubmitTemplate,
  useTemplate,
} from "../../providers/Templates";
import useCurrentApi from "../../hooks/useCurrentApi";
import BackendStep from "./Steps/BackendStep";
import FormStep from "./Steps/FormStep";
import BridgeStep from "./Steps/BridgeStep";

const { Button } = wp.components;
const { useMemo, useState, useEffect, useRef } = wp.element;
const apiFetch = wp.apiFetch;
const { __ } = wp.i18n;

const DEFAULT_STEPS = [
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
  wired,
  setWired,
  onDone,
}) {
  const api = useCurrentApi();

  const sortedSteps = useMemo(() => {
    return DEFAULT_STEPS.reduce((steps, defaultStep, i) => {
      if (steps.find((step) => step.name === defaultStep.name)) {
        return steps;
      }

      return steps.concat({ order: i * 10, ...defaultStep });
    }, steps)
      .filter(({ component }) => component)
      .sort((a, b) => a.order - b.order);
  }, [steps]);

  const config = useTemplateConfig(integration);
  const [template] = useTemplate();
  const submitTemplate = useSubmitTemplate();

  const [step, setStep] = useState(0);

  const fields = useMemo(() => config?.fields || [], [config]);

  const groups = useMemo(() => {
    return fields.reduce((groups, field) => {
      const group = refToGroup(field.ref);

      return {
        ...groups,
        [group]: (groups[group] || []).concat([field]),
      };
    }, {});
  }, [fields]);

  const defaults = useMemo(() => {
    const template = Object.fromEntries(
      Object.keys(groups).map((group) => [group, {}])
    );

    return fields.reduce((defaults, field) => {
      if (field.default) {
        const group = refToGroup(field.ref);
        defaults[group] = defaults[group];
        defaults[group][field.name] = field.default;
      } else if (field.type === "options" && field.required) {
        const group = refToGroup(field.ref);
        defaults[group] = defaults[group] || {};
        defaults[group][field.name] = field.options[0]?.value;
      }

      return defaults;
    }, template);
  }, [fields, groups]);

  useEffect(() => {
    setData(defaults);
    setStep(0);
  }, [defaults]);

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

    return groupFields.reduce((isValid, field) => {
      const value = data[group]?.[field.name]; // || defaults[group]?.[field.name];
      return isValid && (!!value || !field.required);
    }, true);
  }, [fields, step, data]);

  const { name: group, component: StepComponent } = useMemo(
    () => sortedSteps[step],
    [sortedSteps, step]
  );

  const submit = () => {
    if (!isValid) return;

    submitTemplate({
      template,
      integration,
      fields: fields.map((field) => {
        const group = refToGroup(field.ref);

        if (
          Object.prototype.hasOwnProperty.call(data[group], field.name) &&
          data[group][field.name] !== null
        ) {
          if (
            field.type === "boolean" &&
            Array.isArray(data[group][field.name])
          ) {
            field.value = !!data[group][field.name][0];
          } else {
            field.value = data[group][field.name];
          }
        } else if (field.default) {
          field.value = field.default;
        } else if (!field.required) {
          switch (field.type) {
            // case "text":
            //   field.value = "";
            //   break;
            // case "number":
            //   field.value = 0;
            //   break;
            case "options":
              field.value = [];
              break;
            case "boolean":
              field.value = false;
              break;
            // default:
            //   field.value = "";
          }
        }

        return field;
      }),
    }).finally(() => onDone());
  };

  const patchData = (patch = null) => {
    const groupDefaults = defaults[group] || {};
    const current = data[group] || {};

    if (patch !== null) {
      patch = {
        ...groupDefaults,
        ...current,
        ...patch,
      };
    } else {
      patch = {};
    }

    setData({ ...data, [group]: patch });
  };

  const pingBackend = useRef((api, backend, credential = {}) => {
    if (!backend?.name) return;

    backend = {
      name: backend.name,
      base_url: backend.base_url,
      headers: Object.keys(backend)
        .filter((key) => !["name", "base_url"].includes(key))
        .map((key) => ({
          name: key,
          value: backend[key],
        })),
    };

    apiFetch({
      path: `forms-bridge/v1/${api}/ping`,
      method: "POST",
      data: { backend, credential },
    })
      .then(({ success }) => setWired(success))
      .catch(() => setWired(false));
  }).current;

  const fromBackend = useRef(data.backend);
  useEffect(() => {
    if (JSON.stringify(data.backend) !== JSON.stringify(fromBackend.current)) {
      setWired(null);
    }

    return () => {
      fromBackend.current = data.backend;
    };
  }, [data.backend]);

  const timeout = useRef();
  useEffect(() => {
    clearTimeout(timeout.current);

    if (group !== "backend" || !isStepDone || wired !== null) return;

    timeout.current = setTimeout(
      () => pingBackend(api, data.backend, data.credential),
      500
    );
  }, [wired, isStepDone, data.backend]);

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

  const canGoForward = isStepDone && (group === "backend" ? wired : true);

  if (!config || !config.fields.length) return;
  if (data[group] === undefined) return;

  return (
    <div style={{ width: "575px", minHeight: "125px" }}>
      <hr style={{ margin: "1rem 0" }} />
      <StepComponent
        integration={integration}
        fields={groups[group] || []}
        data={data[group] || {}}
        setData={patchData}
        wired={wired}
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
            disabled={!canGoForward}
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
