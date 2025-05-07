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

  const lastBridge = useRef(null);

  useEffect(() => {
    setError(false);

    if (bridge.name !== "add") {
      invalidate(
        bridge.name !== lastBridge.current?.name ||
          bridge.backend !== lastBridge.current?.backend ||
          bridge.endpoint !== lastBridge.current?.endpoint ||
          bridge.credential !== lastBridge.current?.credential
      );
    }

    return () => {
      if (bridge.name !== "add") {
        lastBridge.current = bridge;
      }
    };
  }, [bridge]);

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
      .catch((err) => {
        setSchema(null);
        setError(true);
      })
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
    if (error) return null;
    if (!loading && invalid) fetch(api, bridge.endpoint, backend, credential);
    return schema;
  }, [api, error, loading, invalid, schema, bridge, backend, credential]);

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
