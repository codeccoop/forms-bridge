import { useError } from "../providers/Error";
import useTab from "../hooks/useTab";
import {
  fieldsToPayload,
  schemaToPayload,
  applyMappers,
  payloadToFields,
  checkType,
  payloadToSchema,
} from "../lib/payload";
import useBackends from "../hooks/useBackends";

const apiFetch = wp.apiFetch;
const { createContext, useContext, useState, useEffect, useMemo, useCallback } =
  wp.element;
const { __ } = wp.i18n;

const WorkflowContext = createContext({
  jobs: [],
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
      missing.delete(key);

      const schema = payloadToSchema(payload[key]);
      const input = job.input.find((field) => field.name === key);
      const typeCheck = checkType(schema, input.schema, false);

      if (typeCheck && typeCheck !== true) {
        mutated.add(key);
      }
    }
  });

  if (Array.from(missing).length) {
    return [payload, { missing, exit, enter, mutated, touched }];
  }

  job.output.forEach((output) => {
    const input = job.input.find((field) => field.name === output.name);
    const exists = Object.prototype.hasOwnProperty.call(payload, output.name);

    let addToPayload;
    if (input) {
      if (!exists) {
        if (!output.forward) {
          addToPayload = true;
          enter.add(output.name);

          if (!checkType(input.schema, output.schema)) {
            touched.add(output.name);
            addToPayload = true;
          }
        }
      } else if (output.touch) {
        addToPayload = true;
        touched.add(output.name);
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
  backend,
  customFields,
  mutations,
  workflow,
}) {
  const [, setError] = useError();

  const [addon] = useTab();
  const [isLoading, setIsLoading] = useState(false);
  const [step, setStep] = useState(0);
  const [jobs, setJobs] = useState([]);

  useEffect(() => {
    if (!workflow.length || !addon) {
      setJobs([]);
      return;
    }

    const newJobNames = workflow
      .filter((jobName) => {
        return jobs.find((job) => job.name === jobName) === undefined;
      })
      .reduce((jobNames, jobName) => {
        if (!jobNames.includes(jobName)) {
          jobNames.push(jobName);
        }

        return jobNames;
      }, []);

    if (newJobNames.length) {
      fetchJobs(newJobNames).then((newJobs) => {
        newJobs = jobs
          .filter((job) => {
            workflow.indexOf(job.name) !== -1;
          })
          .concat(newJobs)
          .sort((a, b) => {
            return workflow.indexOf(a.name) - workflow.indexOf(b.name);
          });

        setJobs(newJobs);
      });
    } else {
      const newJobs = workflow.map((jobName) => {
        return jobs.find((job) => job.name === jobName);
      });
      setJobs(newJobs);
    }
  }, [addon, workflow]);

  const fetchJobs = useCallback(
    (workflow) => {
      setIsLoading(true);

      return apiFetch({
        path: `forms-bridge/v1/${addon}/jobs/workflow`,
        method: "POST",
        data: { jobs: workflow },
      })
        .catch(() => {
          setError(__("Loading workflow job error", "forms-bridge"));
          return [];
        })
        .finally(() => setIsLoading(false));
    },
    [addon]
  );

  const [backends] = useBackends();
  const includeFiles = useMemo(() => {
    const headers =
      backends.find(({ name }) => name === backend)?.headers || [];
    const contentType = headers.find(
      (header) => header.name === "Content-Type"
    )?.value;
    return contentType !== undefined && contentType !== "multipart/form-data";
  }, [backends, backend]);

  const workflowJobs = useMemo(
    () =>
      [
        {
          name: "form-job",
          title: __("Form submission", "forms-bridge"),
          description: __(
            "Form submission after mappers has been applied",
            "forms-bridge"
          ),
          mappers: mutations[0] || [],
          input: [],
          output: [],
        },
      ]
        .concat(
          jobs.map((job, i) => ({
            ...job,
            mappers: mutations[i + 1] || [],
          }))
        )
        .concat([
          {
            name: "output-job",
            title: __("Output payload", "forms-bridge"),
            description: __("Workflow output payload", "forms-bridge"),
            mappers: [],
            input: [],
            output: [],
          },
        ]),
    [mutations, jobs]
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
      }, [])
      .concat(
        customFields.map(({ name }) => ({
          name,
          label: name,
          schema: { type: "string" },
        }))
      );
  }, [form]);

  const stage = useMemo(() => {
    let payload = fieldsToPayload(formFields);
    let diff;

    let i;
    for (i = 0; i <= step; i++) {
      if (diff?.missing && !Array.from(diff.missing).length) {
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
      value={{ jobs, workflow: workflowJobs, isLoading, step, setStep, stage }}
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
  const { step, setStep, workflow = [] } = useContext(WorkflowContext);
  return [step, setStep, workflow.length - 1];
}

export function useWorkflowJobs() {
  const { jobs, isLoading } = useContext(WorkflowContext);

  if (isLoading) return [];
  return jobs;
}

export function useWorkflowJob() {
  const { step, workflow, isLoading } = useContext(WorkflowContext);

  if (isLoading) return;
  return workflow[step];
}
