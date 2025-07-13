import useTab from "../hooks/useTab";
import { useLoading } from "./Loading";
import { useError } from "./Error";

const { createContext, useContext, useMemo, useEffect, useState, useCallback } =
  wp.element;
const apiFetch = wp.apiFetch;
const { __ } = wp.i18n;

const SchemasContext = createContext({
  template: {},
  bridge: {},
  fetch: {},
});

export default function SchemasProvider({ children }) {
  const [tab] = useTab();
  const [schemas, setSchemas] = useState({});

  const [, setLoading] = useLoading();
  const [, setError] = useError();

  const fetch = useCallback(
    (addon) => {
      if (!addon || schemas[addon]) return;

      setLoading(true);

      apiFetch({
        path: `forms-bridge/v1/${addon}/schemas`,
      })
        .then((schema) => setSchemas({ ...schemas, [addon]: schema }))
        .catch(() => setError(__("Schema loading error", "forms-bridge")))
        .finally(() => setLoading(false));
    },
    [schemas]
  );

  useEffect(() => {
    if (tab && tab !== "general") fetch(tab);
  }, [tab]);

  const schema = useMemo(() => {
    const { bridge, credential } = schemas[tab] || {};

    return {
      bridge,
      credential,
    };
  }, [tab, schemas]);

  return (
    <SchemasContext.Provider value={schema}>{children}</SchemasContext.Provider>
  );
}

export function useSchemas() {
  return useContext(SchemasContext);
}
