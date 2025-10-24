import useTab from "../hooks/useTab";
import { useLoading } from "./Loading";
import { useError } from "./Error";

const {
  createContext,
  useContext,
  useMemo,
  useEffect,
  useRef,
  useState,
  useCallback,
} = wp.element;
const apiFetch = wp.apiFetch;
const { __ } = wp.i18n;

const SchemasContext = createContext({
  backend: {},
  credential: {},
  bridge: {},
});

export default function SchemasProvider({ children }) {
  const [tab] = useTab();
  const [schemas, setSchemas] = useState({});
  const schemasRef = useRef(schemas);
  schemasRef.current = schemas;

  const [, setLoading] = useLoading();
  const [, setError] = useError();

  const fetch = useCallback(
    (addon) => {
      if (!addon || schemasRef.current[addon]) return;

      setLoading(true);

      apiFetch({
        path: `forms-bridge/v1/${addon}/schemas`,
      })
        .then((schema) =>
          setSchemas({ ...schemasRef.current, [addon]: schema })
        )
        .catch(() => setError(__("Schema loading error", "forms-bridge")))
        .finally(() => setLoading(false));
    },
    [schemas]
  );

  useEffect(() => {
    fetch("http");
  }, []);

  useEffect(() => {
    if (tab && tab !== "general" && tab !== "http") fetch(tab);
  }, [fetch, tab]);

  const schema = useMemo(() => {
    const { bridge } = schemasRef.current[tab] || {};
    const { backend, credential } = schemasRef.current.http || {};

    return {
      bridge,
      credential,
      backend,
    };
  }, [tab, schemas]);

  return (
    <SchemasContext.Provider value={schema}>{children}</SchemasContext.Provider>
  );
}

export function useSchemas() {
  return useContext(SchemasContext);
}
