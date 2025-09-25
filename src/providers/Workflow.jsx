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
import { useForms } from "./Forms";
import { useJobConfig } from "./Jobs";
import diff from "../lib/diff";
import { isset } from "../lib/utils";

const apiFetch = wp.apiFetch;
const {
  createContext,
  useContext,
  useState,
  useEffect,
  useMemo,
  useCallback,
  useRef,
} = wp.element;
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
  const enter = new Set();
  const missing = new Set();

  if (!job) return [payload, { exit, mutated, enter, missing }];

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
    return [payload, { missing, exit, enter, mutated }];
  }

  job.output.forEach((output) => {
    const requires = Array.isArray(output.requires)
      ? output.requires.filter((name) => !isset(payload, name))
      : [];

    if (requires.length) {
      return;
    }

    const input = job.input.find((field) => field.name === output.name);
    const exists = isset(payload, output.name);

    let addToPayload = false;
    if (input) {
      const typeCheck = checkType(input.schema, output.schema);
      if (!typeCheck) {
        mutated.add(output.name);
        addToPayload = true;
      }

      if (!exists) {
        enter.add(output.name);
        addToPayload = true;
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
    const exists = isset(payload, input.name);
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
  formId,
  includeFiles,
  customFields = [],
  mutations = [],
  workflow = [],
}) {
  const [addon] = useTab();
  const [error, setError] = useError();

  const [jobOnEditor] = useJobConfig();

  const [isLoading, setIsLoading] = useState(false);
  const [step, setStep] = useState(0);
  const [jobs, setJobs] = useState([]);

  const [forms] = useForms();
  const form = useMemo(
    () => forms.find((form) => form._id === formId),
    [forms, formId]
  );

  const fetchSignal = useRef();
  const fetchJobs = useCallback(
    (workflow) => {
      setIsLoading(true);

      if (fetchSignal.current) {
        fetchSignal.current.abort();
      }

      fetchSignal.current = new AbortController();

      return apiFetch({
        path: `forms-bridge/v1/${addon}/jobs/workflow`,
        method: "POST",
        data: { jobs: workflow },
        signal: fetchSignal.current.signal,
      })
        .catch((err) => {
          if (err.name === "AbortError") return;

          setError(__("Loading workflow job error", "forms-bridge"));
          return [];
        })
        .finally(() => {
          setIsLoading(false);
          fetchSignal.current = null;
        });
    },
    [addon]
  );

  useEffect(() => {
    if (jobs.length && (!workflow.length || !addon)) {
      setJobs([]);
      return;
    }

    if (error) {
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
        if (newJobs === undefined) return;

        newJobs = jobs
          .filter((job) => workflow.indexOf(job.name) !== -1)
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

      if (newJobs.length < jobs.length) {
        setJobs(newJobs);
      }
    }
  }, [addon, jobs, workflow, fetchJobs]);

  const workflowJobs = useMemo(() => {
    const workflowJobs = workflow
      .map((name) => jobs.find((j) => j.name === name))
      .filter((j) => j)
      .map((j) => ({ ...j }));

    return [
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
        workflowJobs.map((job, i) => ({
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
      ]);
  }, [workflow, mutations, jobs]);

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
  }, [form, customFields, includeFiles]);

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

  useEffect(() => {
    if (!jobOnEditor?.name) return;

    const index = workflow.findIndex((name) => jobOnEditor.name === name);
    if (index === -1) {
      return;
    }

    const workflowJob = { ...workflowJobs[index + 1] };
    delete workflowJob.mappers;

    const changed = diff(jobOnEditor, workflowJob);
    if (!changed) {
      return;
    }

    fetchJobs([jobOnEditor.name]).then((newJobs) => {
      if (newJobs === undefined) return;

      newJobs = jobs
        .slice(0, index)
        .concat(newJobs)
        .concat(jobs.slice(index + 1, jobs.lenght));

      setJobs(newJobs);
    });
  }, [jobOnEditor, jobs, workflowJobs, fetchJobs]);

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
  return workflow?.[step];
}
