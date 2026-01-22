// source
import { useLoading } from "../providers/Loading";
import { useError } from "../providers/Error";
import useTab from "../hooks/useTab";
import { useSettings } from "./Settings";

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

  const [settings, submitSettings] = useSettings();

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
        .then((config) => {
          setJob(config.name);
          setConfig(config);
        })
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
        .then((config) => {
          if (config?.name) {
            setJob(config.name);
            setConfig(config);
          } else {
            setConfig(null);
            setJob(null);
            submitSettings(settings);
          }
        })
        .catch((err) => {
          console.error(err);
          setError(__("Job reset error", "forms-bridge"));
        })
        .finally(() => setLoading(false));
    },
    [addon, settings]
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
