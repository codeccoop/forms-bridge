import { useForms } from "./Forms";
import { useWorkflowJobs } from "./WorkflowJobs";

const { createContext, useContext, useState, useMemo } = wp.element;
const { __ } = wp.i18n;

const WorkflowContext = createContext({ jobs: [] });

function clone(obj) {
  return JSON.parse(JSON.stringify(obj));
}

// function fieldJsonType(field) {
//   switch (type) {
//     case "email":
//     case "url":
//     case "text":
//     case "textarea":
//     case "date":
//     case "hidden":
//       return "string";
//     case "options":
//     default:
//       return type;
//   }
// }

function applyMappers(fields, mappers) {
  return fields
    .map(clone)
    .map((field) => {
      mappers.forEach((mapper) => {
        if (mapper.from === field.name) {
          field.name = mapper.cast === "null" ? null : mapper.to;
        }
      });

      return field;
    })
    .filter((field) => field.name)
    .reduce((fields, field) => {
      if (!fields.map(({ name }) => name).includes(field.name)) {
        fields.push(field);
      }

      return fields;
    }, []);
}

function applyJob(fields, job) {
  if (!job) return fields;

  if (job.mappers) return applyMappers(fields, job.mappers);

  const missing = job.input.filter(
    (field) => field.required && !fields.find(({ name }) => name === field.name)
  );

  if (missing.length) return fields;

  return fields
    .filter((field) => field.exit !== true)
    .map(clone)
    .map((field) => {
      field.isInput =
        job.input.findIndex(({ name }) => name === field.name) !== -1;

      field.exit =
        field.isInput &&
        job.output.findIndex(({ name }) => name === field.name) === -1;

      field.isNew = false;
      return field;
    })
    .concat(
      job.output
        .filter((field) => !fields.find(({ name }) => name === field.name))
        .filter((field) => !job.input.find(({ name }) => name === field.name))
        .map(clone)
        .map((field) => {
          field.isNew = true;
          return field;
        })
    );
}

export default function WorkflowProvider({
  children,
  formId,
  mappers,
  workflow,
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
      mappers,
    }),
    [mappers]
  );

  const workflowJobs = useMemo(
    () => [mappersJob].concat(jobs),
    [jobs, mappersJob]
  );

  const forms = useForms();
  const formFields = useMemo(() => {
    const form = forms.find(({ _id }) => _id === formId);
    return form?.fields || [];
  }, [formId, forms]);

  const stage = useMemo(() => {
    let stage = formFields;

    for (let i = 0; i <= step; i++) {
      stage = applyJob(stage, workflowJobs[i]);
    }

    return stage;
  }, [step, workflowJobs]);

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
