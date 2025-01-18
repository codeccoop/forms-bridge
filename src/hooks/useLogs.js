// vendor
import apiFetch from "@wordpress/api-fetch";
import { useState, useEffect, useRef } from "@wordpress/element";

export default function useLogs({ debug }) {
  const [logs, setLogs] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(false);

  const interval = useRef(null);

  const fetch = () => {
    setLoading(true);
    return apiFetch({
      path: `${window.wpApiSettings.root}forms-bridge/v1/logs`,
      headers: {
        "X-WP-Nonce": wpApiSettings.nonce,
      },
    })
      .then((logs) => setLogs(logs))
      .catch(() => setError(true))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    if (!debug) return;

    fetch();

    interval.current = setInterval(() => fetch(), 3e4);
    return () => {
      clearInterval(interval.current);
    };
  }, [debug]);

  useEffect(() => {
    if (error) {
      setLogs([]);
    }
  }, [error]);

  return {
    loading,
    error,
    logs,
  };
}
