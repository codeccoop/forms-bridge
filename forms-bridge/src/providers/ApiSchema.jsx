import useTab from "../hooks/useTab";
import { useBackends } from "../hooks/useHttp";

const apiFetch = wp.apiFetch;
const { createContext, useContext, useRef, useState, useMemo, useEffect } =
  wp.element;

const ApiSchemaContext = createContext([]);

export default function ApiSchemaProvider({ children, bridge }) {
  const [addon] = useTab();
  const [backends] = useBackends();

  const [loading, setLoading] = useState(false);
  const schemas = useRef(new Map()).current;
  const [, updates] = useState(0);

  const backend = useMemo(
    () => backends.find(({ name }) => bridge?.backend === name),
    [backends, bridge]
  );

  const key = useMemo(
    () =>
      JSON.stringify({
        endpoint: bridge?.endpoint,
        backend,
      }),
    [bridge?.endpoint, backend]
  );

  const addSchema = (key, schema) => {
    schemas.set(key, schema);
    updates((i) => i + 1);
  };

  const fetch = (key, endpoint, backend) => {
    setLoading(true);

    apiFetch({
      path: `forms-bridge/v1/${addon}/backend/endpoint/schema`,
      method: "POST",
      data: { endpoint, backend },
    })
      .then((schema) => addSchema(key, schema))
      .catch(() => addSchema(key, []))
      .finally(() => setLoading(false));
  };

  const timeout = useRef();
  useEffect(() => {
    clearTimeout(timeout.current);

    if (!backend || !bridge?.endpoint || loading || schemas.get(key)) return;

    timeout.current = setTimeout(
      () => fetch(key, bridge.endpoint, backend),
      400
    );
  }, [key, bridge, backend]);

  const schema = schemas.get(key);
  return (
    <ApiSchemaContext.Provider value={schema}>
      {children}
    </ApiSchemaContext.Provider>
  );
}

export function useApiFields() {
  const schema = useContext(ApiSchemaContext);
  return schema || [];
}
