const apiFetch = wp.apiFetch;
const { useState, useEffect, useRef } = wp.element;

export default function useLogs({ debug }) {
  const [logs, setLogs] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(false);

  const interval = useRef(null);

  const fetch = () => {
    setLoading(true);
    return apiFetch({
      path: "forms-bridge/v1/logs?lines=1000",
      signal: AbortSignal.timeout(3000),
    })
      .then((logs) => setLogs(logs))
      .catch(() => setError(true))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    if (!debug) return;

    setTimeout(fetch, 1e3);
    interval.current = setInterval(() => fetch(), 1e4);

    return () => {
      clearInterval(interval.current);
    };
  }, [debug]);

  useEffect(() => {
    if (error) setLogs([]);
  }, [error]);

  return {
    loading,
    error,
    logs,
  };
}
