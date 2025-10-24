// source
import { useLoading } from "../providers/Loading";
import { useError } from "../providers/Error";
import useTab from "../hooks/useTab";
import { useFetchSettings } from "./Settings";
import { useForms } from "./Forms";

const apiFetch = wp.apiFetch;
const { createContext, useContext, useEffect, useState, useCallback } =
  wp.element;
const { __ } = wp.i18n;

const TemplatesContext = createContext({
  template: null,
  setTemplate: () => {},
  config: null,
  submit: () => {},
  reset: () => {},
});

export default function TemplatesProvider({ children }) {
  const [, setLoading] = useLoading();
  const [, setError] = useError();

  const [addon] = useTab();
  const [template, setTemplate] = useState(null);
  const [config, setConfig] = useState(null);

  const [, fetchForms] = useForms();
  const fetchSettings = useFetchSettings();

  useEffect(() => {
    if (!template) {
      setConfig(null);
    } else {
      fetchConfig(template);
    }
  }, [template]);

  const fetchConfig = useCallback(
    (template) => {
      return apiFetch({
        path: `forms-bridge/v1/${addon}/templates/${template}`,
      })
        .then((config) => {
          if (!template) return;
          setConfig(config);
        })
        .catch(() =>
          setError("error", __("Template config load error", "forms-bridge"))
        );
    },
    [addon]
  );

  const submit = useCallback(
    ({ fields, integration }) => {
      if (!template) {
        return Promise.reject();
      }

      setLoading(true);

      return apiFetch({
        path: `forms-bridge/v1/${addon}/templates/${template}/use`,
        method: "POST",
        data: {
          integration,
          fields,
        },
      })
        .then(({ success }) => {
          if (success) {
            fetchForms().then(fetchSettings);
          }

          return success;
        })
        .catch(() => setError(__("Template submit error", "forms-bridge")))
        .finally(() => setLoading(false));
    },
    [addon, template]
  );

  const reset = useCallback(() => {
    if (!template) {
      return Promise.reject();
    }

    setLoading(true);

    return apiFetch({
      path: `forms-bridge/v1/${addon}/templates/${template}`,
      method: "DELETE",
    })
      .then((config) => {
        if (!config) setConfig(null);
        else setConfig(config);
      })
      .catch(() => setError(__("Template reset error", "forms-bridge")))
      .finally(() => setLoading(false));
  }, [addon, template]);

  return (
    <TemplatesContext.Provider
      value={{
        template,
        setTemplate,
        config,
        submit,
        reset,
      }}
    >
      {children}
    </TemplatesContext.Provider>
  );
}

export function useTemplate() {
  const { template, setTemplate } = useContext(TemplatesContext);
  return [template, setTemplate];
}

export function useTemplateConfig() {
  const { config, submit, reset } = useContext(TemplatesContext);
  return [config, submit, reset];
}
