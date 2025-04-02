import {
  fieldsToPayload,
  schemaToPayload,
  applyMappers,
  payloadToFields,
  checkType,
  payloadToSchema,
} from "../lib/payload";
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

function applyJob(payload, job) {
  const exit = new Set();
  const mutated = new Set();
  const touched = new Set();
  const enter = new Set();
  const missing = new Set();

  if (!job) return [payload, { exit, mutated, touched, enter, missing }];

  job.input
    .filter((field) => field.required)
    .forEach((field) => missing.add(field.name));

  Object.keys(payload).forEach((key) => {
    if (missing.has(key)) {
      const schema = payloadToSchema(payload[key]);
      const input = job.input.find((field) => field.name === key);
      const typeCheck = checkType(schema, input.schema, false);
      if (typeCheck === true) {
        missing.delete(key);
      } else if (typeCheck) {
        missing.delete(key);
        mutated.add(key);
      }
    }
  });

  if (missing.values().some(() => true)) {
    return [payload, { missing, exit, enter, mutated, touched }];
  }

  job.output.forEach((output) => {
    const input = job.input.find((field) => field.name === output.name);

    let addToPayload;
    if (input) {
      addToPayload = Object.prototype.hasOwnProperty.call(payload, input.name);

      if (addToPayload) {
        if (output.touch) {
          touched.add(output.name);
        }

        if (!checkType(input, output)) {
          mutated.add(output.name);
        }
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

  return [payload, { missing, enter, exit, mutated, touched }];
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

  const formJob = useMemo(
    () => ({
      name: "form-job",
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
      [formJob].concat(
        jobs.map((job, i) => ({
          ...job,
          mappers: mutations[i + 1] || [],
        }))
      ),
    [mutations, jobs, formJob]
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

    let i;
    for (i = 0; i <= step; i++) {
      if (diff?.missing && !diff.missing.values().some(() => true)) {
        payload = applyMappers(payload, workflowJobs[i - 1]?.mappers || []);
      }

      [payload, diff] = applyJob(payload, workflowJobs[i]);
    }

    const fields = payloadToFields(payload);

    if (workflowJobs[i - 1]?.name === "form-job") {
      fields.forEach((field) => diff.enter.add(field.name));
    }

    return [fields, diff];
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
