import { useGeneral } from "./Settings";
import useCurrentApi from "../hooks/useCurrentApi";

const apiFetch = wp.apiFetch;
const { createContext, useContext, useEffect, useRef, useState, useMemo } =
  wp.element;

const ApiSchemaContext = createContext([]);

export default function ApiSchemaProvider({ children, bridge, credentials }) {
  const [{ backends }] = useGeneral();
  const api = useCurrentApi();

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(false);
  const [schema, setSchema] = useState(null);
  const [invalid, invalidate] = useState(true);

  if (error) {
    setSchema(null);
  }

  const lastBridge = useRef(bridge.name);
  useEffect(() => {
    setError(false);
    invalidate(bridge.name !== "add" && bridge.name !== lastBridge.current);

    return () => {
      if (bridge.name !== "add") {
        lastBridge.current = bridge.name;
      }

      invalidate(true);
    };
  }, [api, bridge]);

  const fetch = useRef((api, endpoint, backend, credential = {}) => {
    if (!backend || !api) return;

    setLoading(true);

    apiFetch({
      path: `forms-bridge/v1/${api}/schema`,
      method: "POST",
      data: { backend, credential, endpoint },
    })
      .then((schema) => {
        setSchema(schema);
        invalidate(false);
      })
      .catch(() => setError(true))
      .finally(() => setLoading(false));
  }).current;

  const backend = useMemo(
    () => backends.find(({ name }) => bridge.backend === name),
    [backends, bridge]
  );

  const credential = useMemo(
    () => credentials.find(({ name }) => bridge.credential === name),
    [credentials, bridge]
  );

  const value = useMemo(() => {
    console.log({ loading, invalid });
    if (!loading && invalid) fetch(api, bridge?.endpoint, backend, credential);
    return schema;
  }, [api, loading, invalid, schema, bridge.endpoint, backend, credential]);

  return (
    <ApiSchemaContext.Provider value={value}>
      {children}
    </ApiSchemaContext.Provider>
  );
}

export function useApiFields() {
  const schema = useContext(ApiSchemaContext);
  return schema || [];
}

export function useApiContentType() {
  const schema = useContext(ApiSchemaContext);
  return schema?.content_type;
}
