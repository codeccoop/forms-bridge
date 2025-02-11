// source
import { useApis } from "./Settings";
import { useStoreFetch } from "./Store";

const apiFetch = wp.apiFetch;
const { createContext, useContext, useEffect, useState, useMemo, useRef } =
  wp.element;
const { __ } = wp.i18n;

const TemplatesContext = createContext({
  template: null,
  api: null,
});

export default function TemplatesProvider({ children }) {
  const [apis] = useApis();

  const [api, setApi] = useState(null);
  const [template, setTemplate] = useState(null);
  const [config, setConfig] = useState(null);

  const templates = useMemo(() => {
    if (!api) return [];
    return apis[api]?.templates || [];
  }, [api, apis]);

  const onApi = useRef((api) => setApi(api)).current;

  useEffect(() => {
    wpfb.on("api", onApi);

    return () => {
      wpfb.off("api", onApi);
    };
  }, []);

  useEffect(() => {
    if (!template) {
      setConfig(null);
    } else {
      fetchConfig(template);
    }
  }, [template]);

  const fetchConfig = (template) => {
    return apiFetch({
      path: "forms-bridge/v1/templates/" + template,
    })
      .then(setConfig)
      .catch(() => {
        wpfb.emit("error", __("Loading config error", "forms-bridge"));
      });
  };

  const submit = ({ fields, integration }) => {
    if (!template) {
      return;
    }

    wpfb.emit("loading", true);

    return apiFetch({
      path: "forms-bridge/v1/templates/" + template,
      method: "POST",
      data: {
        template,
        integration,
        fields,
      },
    })
      .then(() => wpfb.emit("flushStore"))
      .catch(() => {
        wpfb.emit("error", __("Template submit error", "forms-bridge"));
      })
      .finally(() => wpfb.emit("loading", false));
  };

  return (
    <TemplatesContext.Provider
      value={{
        template,
        setTemplate,
        templates,
        config,
        submit,
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

export function useTemplates() {
  const { templates } = useContext(TemplatesContext);
  return templates || [];
}

export function useConfig() {
  const { config } = useContext(TemplatesContext);
  return config;
}

export function useSubmitTemplate() {
  const { submit } = useContext(TemplatesContext);
  return (data) => submit(data);
}
