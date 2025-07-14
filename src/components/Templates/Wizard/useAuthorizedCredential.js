import useTab from "../../../hooks/useTab";
import { useLoading } from "../../../providers/Loading";
import { useSchemas } from "../../../providers/Schemas";
import { useFetchSettings } from "../../../providers/Settings";
import { useTemplateConfig } from "../../../providers/Templates";
import { validateCredential } from "./lib";

const { useState, useEffect, useRef, useMemo, useCallback } = wp.element;
const apiFetch = wp.apiFetch;

export default function useAuthorizedCredential({ data = {}, fields = [] }) {
  const [addon] = useTab();

  const [loading, setLoading] = useLoading();
  const [error, setError] = useState(false);

  const focusRef = useRef(true);
  const [focus, setFocus] = useState(true);

  useEffect(() => {
    const onFocus = () => setFocus(true);
    const onBlur = () => setFocus(false);

    window.addEventListener("focus", onFocus);
    window.addEventListener("blur", onBlur);

    return () => {
      window.removeEventListener("focus", onFocus);
      window.removeEventListener("blur", onBlur);
    };
  }, []);

  const { credential: config } = useTemplateConfig()[0] || {};
  const { credential: schema } = useSchemas();

  const credential = useMemo(() => {
    if (!config) return;

    if (validateCredential(data, fields)) {
      const credential = { ...data };

      Object.keys(schema.properties).forEach((prop) => {
        if (
          Object.prototype.hasOwnProperty.call(
            schema.properties[prop],
            "default"
          )
        ) {
          credential[prop] =
            credential[prop] || schema.properties[prop].default;
        }
      });

      return credential;
    }
  }, [data, config, fields]);

  useEffect(() => {
    setError(false);
  }, [credential]);

  const isOauth = Object.prototype.hasOwnProperty.call(
    schema?.properties || {},
    "access_token"
  );

  const authorized = !isOauth || !!data.access_token;

  const fetchSettings = useFetchSettings();
  useEffect(() => {
    if (focus && !focusRef.current && !authorized) {
      fetchSettings();
    }

    return () => {
      focusRef.current = focus;
    };
  }, [focus]);

  const authorize = useCallback(() => {
    if (loading || !credential) return;

    setLoading(true);

    apiFetch({
      path: `forms-bridge/v1/${addon}/oauth/grant`,
      method: "POST",
      data: { credential },
    })
      .then(({ redirect }) => {
        if (redirect === window.location.href) {
          fetchSettings();
        } else {
          window.open(redirect);
        }
      })
      .catch(() => setError(true))
      .finally(() => setLoading(false));
  }, [loading, addon, credential]);

  if (!schema) return [null, true];

  return {
    credential,
    authorized,
    authorize,
    error,
  };
}
