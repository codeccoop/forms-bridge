import useTab from "../hooks/useTab";
import { useBackends } from "../hooks/useHttp";

const apiFetch = wp.apiFetch;
const { createContext, useContext, useRef, useState, useMemo, useEffect } =
  wp.element;

const ApiSchemaContext = createContext([]);

export default function ApiSchemaProvider({ children, bridge }) {
  const [addon] = useTab();
  const [backends] = useBackends();

  const [loadingEndpoints, setLoadingEndpoints] = useState(false);
  const [loadingSchema, setLoadingSchema] = useState(false);

  const endpoints = useRef(new Map()).current;
  const schemas = useRef(new Map()).current;

  const [, endpointUpdates] = useState(0);
  const [, schemaUpdates] = useState(0);

  const backend = useMemo(
    () => backends.find(({ name }) => bridge?.backend === name),
    [backends, bridge]
  );

  const endpointsKey = useMemo(() => {
    if (!backend?.name) return "";

    return JSON.stringify({
      addon,
      method: bridge?.method || "",
      backend: backend?.name,
    });
  }, [addon, bridge?.method, backend?.name]);

  const addEndpoints = (key, list) => {
    endpoints.set(key, list);
    endpointUpdates((i) => i + 1);
  };

  const fetchEndpoints = (key, method, backend) => {
    setLoadingEndpoints(true);

    apiFetch({
      path: `forms-bridge/v1/${addon}/backend/endpoints`,
      method: "POST",
      data: { method, backend },
    })
      .then((endpoints) => addEndpoints(key, endpoints))
      .catch(() => addEndpoints(key, []))
      .finally(() => setLoadingEndpoints(false));
  };

  const endpointsTimeout = useRef();
  useEffect(() => {
    clearTimeout(endpointsTimeout.current);

    if (!bridge || loadingEndpoints || endpoints.get(endpointsKey)) return;

    endpointsTimeout.current = setTimeout(
      () => fetchEndpoints(endpointsKey, bridge.method, backend),
      400
    );
  }, [endpointsKey, bridge, backend]);

  const schemaKey = useMemo(() => {
    if (!bridge?.method || !backend?.name) return "";

    return JSON.stringify({
      addon,
      method: bridge.method,
      endpoint: bridge.endpoint || "/",
      backend: backend.name,
    });
  }, [addon, bridge?.method, bridge?.endpoint, backend?.name]);

  const addSchema = (key, schema) => {
    schemas.set(key, schema);
    schemaUpdates((i) => i + 1);
  };

  const fetchSchema = (key, endpoint, method, backend) => {
    setLoadingSchema(true);

    apiFetch({
      path: `forms-bridge/v1/${addon}/backend/endpoint/schema`,
      method: "POST",
      data: { endpoint, method, backend },
    })
      .then((schema) => addSchema(key, schema))
      .catch(() => addSchema(key, []))
      .finally(() => setLoadingSchema(false));
  };

  const schemaTimeout = useRef();
  useEffect(() => {
    clearTimeout(schemaTimeout.current);

    if (!bridge || !backend || loadingSchema || schemas.get(schemaKey)) return;

    schemaTimeout.current = setTimeout(
      () =>
        fetchSchema(schemaKey, bridge.endpoint || "/", bridge.method, backend),
      400
    );
  }, [schemaKey, bridge, backend]);

  return (
    <ApiSchemaContext.Provider
      value={{
        schema: schemas.get(schemaKey),
        endpoints: endpoints.get(endpointsKey),
      }}
    >
      {children}
    </ApiSchemaContext.Provider>
  );
}

export function useApiFields() {
  const { schema } = useContext(ApiSchemaContext);
  return schema || [];
}

export function useApiEndpoints() {
  const { endpoints } = useContext(ApiSchemaContext);
  return endpoints || [];
}
