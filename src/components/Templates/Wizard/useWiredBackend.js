import useTab from "../../../hooks/useTab";
import diff from "../../../lib/diff";
import { useTemplateConfig } from "../../../providers/Templates";
import { mockBackend, validateBackend } from "./lib";

const { useState, useEffect, useMemo, useCallback, useRef } = wp.element;
const apiFetch = wp.apiFetch;

export default function useWiredBackend({
  data = {},
  fields = [],
  credential = {},
  authorized,
}) {
  const [tab] = useTab();
  const { backend: template } = useTemplateConfig()[0] || {};

  const [wired, setWired] = useState(null);

  useEffect(() => {
    setWired(null);
  }, [template, authorized]);

  const backend = useMemo(() => {
    if (!template) return;

    const backend = mockBackend(data, template, fields);
    if (validateBackend(backend, template, fields)) {
      return backend;
    }
  }, [data, template, fields]);

  const lastBackend = useRef();
  useEffect(() => {
    if (!backend || diff(backend, lastBackend.current)) {
      setWired(null);
    }

    return () => {
      lastBackend.current = backend;
    };
  }, [backend]);

  const ping = useCallback(
    (backend, credential) => {
      apiFetch({
        path: `forms-bridge/v1/${tab}/backend/ping`,
        method: "POST",
        data: { backend, credential },
      })
        .then(({ success }) => setWired(success))
        .catch(() => setWired(false));
    },
    [tab]
  );

  const timeout = useRef();
  useEffect(() => {
    clearTimeout(timeout.current);

    if (!backend || !authorized || wired !== null) return;

    timeout.current = setTimeout(() => ping(backend, credential), 500);
  }, [template, wired, backend, credential, authorized]);

  return [backend, wired];
}
