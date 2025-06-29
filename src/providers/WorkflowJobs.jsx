// source
import { useApis } from "./Settings";
import useCurrentApi from "../hooks/useCurrentApi";

const apiFetch = wp.apiFetch;
const { createContext, useContext, useEffect, useState, useMemo, useRef } =
  wp.element;
const { __ } = wp.i18n;

const WorkflowJobsContext = createContext({
  apiJobs: [],
  workflow: [],
  setWorkflow: () => {},
  workflowJobs: [],
});

export default function WorkflowJobsProvider({ children }) {
  const [apis] = useApis();

  const api = useCurrentApi();
  const [isLoading, setIsLoading] = useState(false);
  const [workflow, setWorkflow] = useState([]);
  const [workflowJobs, setWorkflowJobs] = useState([]);

  const apiJobs = useMemo(() => {
    if (!api) return [];
    return apis[api]?.workflow_jobs || [];
  }, [api, apis]);

  useEffect(() => {
    if (!workflow.length) {
      setWorkflowJobs([]);
      return;
    }

    const newJobNames = workflow
      .filter((jobName) => {
        return workflowJobs.find((job) => job.name === jobName) === undefined;
      })
      .reduce((jobNames, jobName) => {
        if (!jobNames.includes(jobName)) {
          jobNames.push(jobName);
        }

        return jobNames;
      }, []);

    if (newJobNames.length) {
      fetchJobs(newJobNames).then((jobs) => {
        const newJobs = workflowJobs
          .filter((job) => {
            workflow.indexOf(job.name) !== -1;
          })
          .concat(jobs)
          .sort((a, b) => {
            return workflow.indexOf(a.name) - workflow.indexOf(b.name);
          });

        const newWorkflowJobs = workflow.map((jobName) => {
          const job =
            workflowJobs.find((job) => job.name === jobName) ||
            newJobs.find((job) => job.name === jobName);

          return { ...job };
        });

        setWorkflowJobs(newWorkflowJobs);
      });
    } else {
      const newWorkflowJobs = workflow.map((jobName) => {
        return workflowJobs.find((job) => job.name === jobName);
      });
      setWorkflowJobs(newWorkflowJobs);
    }
  }, [workflow]);

  const fetchJobs = (workflow) => {
    if (!api) return;

    setIsLoading(true);

    return apiFetch({
      path: `forms-bridge/v1/${api}/workflow`,
      method: "POST",
      data: { jobs: workflow },
    })
      .catch(() => {
        wpfb.emit("error", __("Loading worflow job error", "forms-bridge"));
      })
      .finally(() => setIsLoading(false));
  };

  return (
    <WorkflowJobsContext.Provider
      value={{ apiJobs, setWorkflow, workflowJobs, isLoading }}
    >
      {children}
    </WorkflowJobsContext.Provider>
  );
}

export function useApiWorkflowJobs() {
  const { apiJobs } = useContext(WorkflowJobsContext);
  return apiJobs || [];
}

export function useWorkflowJobs(workflow) {
  const { workflowJobs, setWorkflow, isLoading } =
    useContext(WorkflowJobsContext);

  useEffect(() => setWorkflow(workflow || []), [workflow]);
  return [workflowJobs, isLoading];
}
