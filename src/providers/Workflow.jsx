import {
  fieldsToPayload,
  schemaToPayload,
  applyMappers,
  payloadToFields,
} from "../components/Mappers/lib";
import { useWorkflowJobs } from "./WorkflowJobs";

const { createContext, useContext, useState, useMemo } = wp.element;
const { __ } = wp.i18n;

const WorkflowContext = createContext({
  workflowJobs: [],
  isLoading: false,
  step: 0,
  setStep: () => {},
  stage: [],
});

function checkType(a, b, strict = true) {
  if (!a || !b) {
    return false;
  }

  if (a.type !== b.type) {
    if (strict) {
      return false;
    } else {
      // do some type compatibility checks
    }
  }

  if (a.type === "object") {
    const props = a.properties || {};

    return Object.keys(props).reduce((typeCheck, key) => {
      if (!typeCheck) return typeCheck;
      if (!b?.properties?.[key]) return false;

      return typeCheck && checkType(a[key], b[key]);
    }, true);
  } else if (a.type === "array") {
    return checkType(a.items, b.items);
  }

  return true;
}

function applyJob(payload, job) {
  const exit = new Set();
  const mutated = new Set();
  const enter = new Set();
  const missing = new Set();

  if (!job) return [payload, { exit, mutated, enter, missing }];

  job.input
    .filter((field) => field.required)
    .forEach((field) => missing.add(field.name));

  Object.keys(payload).forEach((key) => {
    if (missing.has(key)) {
      missing.delete(key);
    }
  });

  if (missing.values().some(() => true)) {
    return [payload, { missing, exit, enter, mutated }];
  }

  job.output.forEach((output) => {
    const input = job.input.find((field) => field.name === output.name);

    let addToPayload;
    if (input) {
      addToPayload = Object.prototype.hasOwnProperty.call(payload, input.name);

      if (addToPayload && (output.touch || !checkType(input, output))) {
        mutated.add(output.name);
      }
    } else {
      addToPayload = true;
      enter.add(output.name);
    }

    if (addToPayload) {
      payload[output.name] = schemaToPayload(output.schema);
    }
  });

  job.input.forEach((input) => {
    const exists = Object.prototype.hasOwnProperty.call(payload, input.name);
    const output = job.output.find((field) => field.name === input.name);

    if (!output && exists) {
      delete payload[input.name];
      exit.add(input.name);
    }
  });

  return [payload, { missing, enter, exit, mutated }];
}

export default function WorkflowProvider({
  children,
  form,
  mutations,
  workflow,
  includeFiles,
}) {
  const [step, setStep] = useState(0);

  const [jobs, isLoading] = useWorkflowJobs(workflow);

  const mappersJob = useMemo(
    () => ({
      title: __("Form submission", "forms-bridge"),
      description: __(
        "Form submission after mappers has been applied",
        "forms-bridge"
      ),
      mappers: mutations[0] || [],
      input: [],
      output: [],
    }),
    [mutations]
  );

  const workflowJobs = useMemo(
    () =>
      [mappersJob].concat(
        jobs.map((job, i) => ({
          ...job,
          mappers: mutations[i + 1] || [],
        }))
      ),
    [mutations, jobs, mappersJob]
  );

  const formFields = useMemo(() => {
    if (!form) return [];

    return form.fields
      .filter(({ is_file }) => includeFiles || !is_file)
      .reduce((fields, { name, label, is_file, schema }) => {
        if (includeFiles && is_file) {
          fields.push({ name, label, schema: { type: "string" } });
          fields.push({
            name: name + "_filename",
            label: name + "_filename",
            schema: { type: "string" },
          });
        } else {
          fields.push({ name, label, schema });
        }

        return fields;
      }, []);
  }, [form]);

  const stage = useMemo(() => {
    let payload = fieldsToPayload(formFields);
    let diff;

    for (let i = 0; i <= step; i++) {
      if (diff?.missing && !diff.missing.values().some(() => true)) {
        payload = applyMappers(payload, workflowJobs[i - 1]?.mappers || []);
      }

      [payload, diff] = applyJob(payload, workflowJobs[i]);
    }

    return [payloadToFields(payload), diff];
  }, [step, workflowJobs, formFields]);

  return (
    <WorkflowContext.Provider
      value={{ workflowJobs, isLoading, step, setStep, stage }}
    >
      {children}
    </WorkflowContext.Provider>
  );
}

export function useWorkflowStage() {
  const { stage } = useContext(WorkflowContext);
  return stage;
}

export function useWorkflowStepper() {
  const { step, setStep } = useContext(WorkflowContext);
  return [step, setStep];
}

export function useWorkflowJob() {
  const { step, workflowJobs, isLoading } = useContext(WorkflowContext);
  if (isLoading) return;
  return workflowJobs[step];
}
