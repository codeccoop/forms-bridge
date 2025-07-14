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
  const { backend: config } = useTemplateConfig()[0] || {};

  const [wired, setWired] = useState(null);

  useEffect(() => {
    setWired(null);
  }, [config, authorized]);

  const backend = useMemo(() => {
    if (!config) return;

    const backend = mockBackend(data, config, fields);
    if (validateBackend(backend, config, fields)) {
      return backend;
    }
  }, [data, config, fields]);

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
  }, [config, wired, backend, credential, authorized]);

  return [backend, wired];
}
