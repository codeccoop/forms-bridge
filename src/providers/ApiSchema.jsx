import useTab from "../hooks/useTab";
import useBackends from "../hooks/useBackends";
import { useCredentials } from "../hooks/useAddon";

const apiFetch = wp.apiFetch;
const { createContext, useContext, useRef, useState, useMemo, useEffect } =
  wp.element;

const ApiSchemaContext = createContext([]);

export default function ApiSchemaProvider({ children, bridge }) {
  const [addon] = useTab();
  const [backends] = useBackends();
  const [credentials] = useCredentials();

  const [loading, setLoading] = useState(false);
  const schemas = useRef(new Map()).current;
  const [, updates] = useState(0);

  const backend = useMemo(
    () => backends.find(({ name }) => bridge?.backend === name),
    [backends, bridge]
  );

  const credential = useMemo(
    () => credentials.find(({ name }) => bridge?.credential === name),
    [credentials, bridge]
  );

  const key = useMemo(
    () =>
      JSON.stringify({
        endpoint: bridge?.endpoint,
        backend,
        credential,
      }),
    [bridge?.endpoint, backend, credential]
  );

  const addSchema = (key, schema) => {
    schemas.set(key, schema);
    updates((i) => i + 1);
  };

  const fetch = (key, endpoint, backend, credential) => {
    setLoading(true);

    apiFetch({
      path: `forms-bridge/v1/${addon}/backend/endpoint/schema`,
      method: "POST",
      data: { endpoint, backend, credential: credential || {} },
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
      () => fetch(key, bridge.endpoint, backend, credential),
      400
    );
  }, [key, bridge, backend, credential]);

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
