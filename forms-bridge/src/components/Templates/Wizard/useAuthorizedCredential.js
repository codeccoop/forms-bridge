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

  const isOauth = data.schema === "Bearer";

  const authorized = !isOauth || !!data.refresh_token;

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
      .then(({ success }) => {
        if (!success) throw "error";

        const form = document.createElement("form");
        form.method = "GET";
        form.action = credential.oauth_url + "/auth";
        form.target = "_blank";

        let innerHTML = `
        <input name="client_id" value="${credential.client_id}" />
        <input name="response_type" value="code" />
        <input name="redirect_uri" value="${restUrl("http-bridge/v1/oauth/redirect")}" />
        <input name="access_type" value="offline" />
        <input name="state" value="${btoa(addon)}" />
        `;

        if (credential.scope) {
          innerHTML += `<input name="scope" value="${credential.scope}" />`;
        }

        form.innerHTML = innerHTML;

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
