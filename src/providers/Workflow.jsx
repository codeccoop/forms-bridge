import JsonFinger from "../components/BridgeMappers/JsonFinger";
import { useForms } from "./Forms";
import { useWorkflowJobs } from "./WorkflowJobs";

const { createContext, useContext, useState, useMemo } = wp.element;
const { __ } = wp.i18n;

const WorkflowContext = createContext({ jobs: [] });

function clone(obj) {
  return JSON.parse(JSON.stringify(obj));
}

function mapperJsonType(cast) {
  switch (cast) {
    case "json":
    case "concat":
    case "csv":
    case "string":
      return "string";
    case "null":
      return null;
    default:
      return cast;
  }
}

function applyMutations(fields, mappers) {
  const mutadedFields = fields
    .map(clone)
    .reduce((fields, field) => {
      mappers.forEach((mapper, i) => {
        if (mapper.from === field.name) {
          if (mapper.cast === "copy" && mapper.to !== field.name) {
            const ignoreAfter =
              mappers.slice(i + 1).find(({ to }) => to === field.name)?.cast ===
              "null";

            if (!ignoreAfter) {
              fields.push(clone(field));
            }
          }

          field.name = mapper.cast === "null" ? null : mapper.to;
          if (mapper.cast !== "copy") {
            field.type = mapperJsonType(mapper.cast);
          }
        }
      });

      if (!field.name) {
        return fields;
      }

      const keys = JsonFinger.parse(field.name);
      if (keys.length > 1) {
        for (let i = 0; i < keys.length; i++) {
          const finger = JsonFinger.build(keys.slice(0, i + 1));

          const mapper = mappers.find(({ from }) => from === finger);
          if (mapper && mapper.cast !== "copy") {
            return fields;
          }
        }

        field.name = keys[0];

        const fieldType = field.type;
        if (typeof keys[1] === "string") {
          field.type = "object";
          field.properties = { [keys[1]]: { type: fieldType } };
        } else {
          field.type = "array";
          field.items = { type: fieldType };
        }
      }

      fields.push(field);
      return fields;
    }, [])
    .reduce((fields, field) => {
      return [field].concat(fields);
    }, [])
    .reduce((fields, field) => {
      if (!fields.map(({ name }) => name).includes(field.name)) {
        fields.push(field);
      }

      return fields;
    }, [])
    .reduce((fields, field) => {
      return [field].concat(fields);
    }, []);

  const mutatedNames = new Set(mutadedFields.map((field) => field.name));
}

function applyJob(fields, job) {
  if (!job) return fields;

  if (job.mappers) return applyMutations(fields, job.mappers);

  const missing = job.input.filter(
    (field) => field.required && !fields.find(({ name }) => name === field.name)
  );

  if (missing.length) return fields;

  return fields
    .filter((field) => field.exit !== true && field.schema)
    .map(clone)
    .map((field) => {
      const inputField = job.input.find(({ name }) => name === field.name);
      const outputField = job.output.find(({ name }) => name === field.name);

      field.isInput = inputField !== undefined;
      field.exit = inputField && !outputField;
      field.mutated =
        inputField && outputField && inputField.type !== outputField.type;

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
    let stage = formFields
      .filter((field) => field.schema)
      .map((field) => ({
        ...field,
        ...field.schema,
      }));

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
