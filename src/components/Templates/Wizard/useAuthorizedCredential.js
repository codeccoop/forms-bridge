import useTab from "../../../hooks/useTab";
import { isset, restUrl } from "../../../lib/utils";
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

  const { credential: template } = useTemplateConfig()[0] || {};
  const { credential: schemas } = useSchemas();

  const credential = useMemo(() => {
    if (!template) return;

    if (validateCredential(data, template, fields)) {
      const credential = { ...data };

      const schema = schemas?.oneOf.find((schema) => {
        return (
          schema.properties.schema.const === credential.schema ||
          schema.properties.schema.enum?.includes(credential.schema)
        );
      });

      if (schema) {
        Object.keys(schema.properties).forEach((prop) => {
          if (isset(schema.properties[prop], "default")) {
            credential[prop] =
              credential[prop] || schema.properties[prop].default;
          }
        });
      }

      return credential;
    }
  }, [schemas, data, template, fields]);

  useEffect(() => {
    setError(false);
  }, [credential]);

  const isOauth = data.schema === "OAuth";

  const authorized = useMemo(() => {
    if (!isOauth || !!data.refresh_token) return true;
    else if (!(data.access_token && data.expires_at)) return false;

    let expirationDate = new Date(data.expires_at);
    if (expirationDate.getFullYear() === 1970) {
      expirationDate = new Date(data.expires_at * 1000);
    }

    return Date.now() < expirationDate.getTime();
  }, [isOauth, data.access_token, data.expires_at]);

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
    if (!credential) return;

    let oauthUrl = credential.oauth_url;
    const matches = oauthUrl.match(/{(\w+)}/);
    if (matches && credential[matches[1]]) {
      oauthUrl = oauthUrl.replace(/{\w+}/, credential[matches[1]]);
    }

    credential.oauth_url = oauthUrl;

    setLoading(true);

    apiFetch({
      path: "http-bridge/v1/oauth/grant",
      method: "POST",
      data: { credential },
    })
      .then(({ success, data }) => {
        if (!success) throw "error";

        const { url, params } = data;
        const form = document.createElement("form");
        form.action = url;
        form.method = "GET";
        form.target = "_blank";

        form.innerHTML = Object.keys(params).reduce((html, name) => {
          const value = params[name];
          if (!value) return html;
          return html + `<input name="${name}" value="${value}" />`;
        }, "");

        form.style.visibility = "hidden";
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
      })
      .catch(() => setError(""))
      .finally(() => setLoading(false));
  }, [loading, addon, credential]);

  if (!schemas) return [null, true];

  return {
    credential,
    authorized,
    authorize,
    error,
  };
}
