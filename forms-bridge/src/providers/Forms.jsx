import { useLoading } from "./Loading";
import { useError } from "./Error";
import { useIntegrations } from "../hooks/useGeneral";
import diff from "../lib/diff";

const { createContext, useContext, useState, useEffect, useRef } = wp.element;
const apiFetch = wp.apiFetch;
const { __ } = wp.i18n;

const FormsContext = createContext([]);

export default function FormsProvider({ children }) {
  const [loading, setLoading] = useLoading();
  const [, setError] = useError();
  const [forms, setForms] = useState([]);

  const invalid = useRef(false);
  const [integrations] = useIntegrations();

  const integrationsRef = useRef(integrations);
  useEffect(() => {
    if (!invalid.current) {
      invalid.current = diff(integrations, integrationsRef.current);
    }

    return () => {
      integrationsRef.current = integrations;
    };
  }, [integrations]);

  useEffect(() => {
    if (loading) return;
    if (invalid.current) {
      fetch().then(() => (invalid.current = false));
    }
  }, [loading, integrations]);

  const fetch = useRef(() => {
    setLoading(true);

    return apiFetch({
      path: "forms-bridge/v1/forms",
    })
      .then(setForms)
      .catch(() => setError(__("Forms loading error", "forms-bridge")))
      .finally(() => setLoading(false));
  }).current;

  return (
    <FormsContext.Provider value={[forms, fetch]}>
      {children}
    </FormsContext.Provider>
  );
}

export function useForms() {
  return useContext(FormsContext);
}
