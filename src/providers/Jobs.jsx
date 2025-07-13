// source
import { useLoading } from "../providers/Loading";
import { useError } from "../providers/Error";
import useTab from "../hooks/useTab";
import useFlushStore from "../hooks/useFlushStore";

const apiFetch = wp.apiFetch;
const { createContext, useContext, useEffect, useState, useCallback } =
  wp.element;
const { __ } = wp.i18n;

const JobsContext = createContext({
  job: null,
  setJob: () => {},
  config: null,
  submit: () => {},
  reset: () => {},
});

export default function JobsProvider({ children }) {
  const [, setLoading] = useLoading();
  const [, setError] = useError();

  const [addon] = useTab();
  const [job, setJob] = useState(null);
  const [config, setConfig] = useState(null);

  const flushStore = useFlushStore();

  useEffect(() => {
    setJob(null);
  }, [addon]);

  useEffect(() => {
    if (job) {
      fetch(job);
    }

    return () => {
      setConfig(null);
    };
  }, [job]);

  const fetch = useCallback(
    (job) => {
      return apiFetch({
        path: `forms-bridge/v1/${addon}/jobs/${job}`,
      })
        .then(setConfig)
        .catch(() => setError(__("Job config load error", "forms-bridge")));
    },
    [addon]
  );

  const submit = useCallback(
    (config) => {
      if (!config?.name) {
        return Promise.reject();
      }

      setLoading(true);

      return apiFetch({
        path: `forms-bridge/v1/${addon}/jobs/${config.name}`,
        method: "POST",
        data: config,
      })
        .then(setConfig)
        .catch(() => setError(__("Job submit error", "forms-bridge")))
        .finally(() => setLoading(false));
    },
    [addon]
  );

  const reset = useCallback(
    (job) => {
      if (!job) {
        setError("error", __("Job reset error", "forms-bridge"));
        return Promise.resolve();
      }

      setLoading(true);

      return apiFetch({
        path: `forms-bridge/v1/${addon}/jobs/${job}`,
        method: "DELETE",
      })
        .then(() => {
          return apiFetch({
            path: `forms-bridge/v1/${addon}/jobs/${job}`,
            method: "GET",
          })
            .then((config) => setConfig(config))
            .catch((err) => {
              if (err.code !== "not_found") throw err;
              flushStore();
              setJob(null);
            });
        })
        .catch(() => setError(__("Job reset error", "forms-bridge")))
        .finally(() => setLoading(false));
    },
    [addon]
  );

  return (
    <JobsContext.Provider
      value={{
        job,
        setJob,
        config,
        submit,
        reset,
      }}
    >
      {children}
    </JobsContext.Provider>
  );
}

export function useJob() {
  const { job, setJob } = useContext(JobsContext);
  return [job, setJob];
}

export function useJobConfig() {
  const { config, submit, reset } = useContext(JobsContext);
  return [config, submit, reset];
}
