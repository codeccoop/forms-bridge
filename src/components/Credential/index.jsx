// source
import RemoveButton from "../RemoveButton";
import { useCredentials } from "../../hooks/useHttp";
import CredentialFields, { INTERNALS } from "./Fields";
import { downloadJson, isset, restUrl } from "../../lib/utils";
import { useLoading } from "../../providers/Loading";
import { useError } from "../../providers/Error";
import diff from "../../lib/diff";
import useResponsive from "../../hooks/useResponsive";
import CopyIcon from "../icons/Copy";
import ArrowDownIcon from "../icons/ArrowDown";
import { useFetchSettings } from "../../providers/Settings";

const { Button } = wp.components;
const { useState, useEffect, useMemo, useRef, useCallback } = wp.element;
const apiFetch = wp.apiFetch;
const { __ } = wp.i18n;

export default function Credential({
  addon,
  data,
  update,
  remove,
  schema: schemas,
  copy,
}) {
  const isResponsive = useResponsive(780);

  const [loading, setLoading] = useLoading();
  const [error, setError] = useError();

  const fetchSettings = useFetchSettings();

  const name = useRef(data.name);
  const [state, setState] = useState({ ...data });

  const schema = useMemo(() => {
    return schemas.oneOf.find(
      (schema) =>
        schema.properties.schema.const === state.schema ||
        schema.properties.schema.enum?.includes(state.schema)
    );
  }, [state.schema]);

  const [credentials] = useCredentials();
  const names = useMemo(() => {
    return new Set(credentials.map((c) => c.name));
  }, [credentials]);

  const nameConflict = useMemo(() => {
    if (!state.name) return false;
    if (name.current.trim() === state.name.trim()) return false;
    return name.current !== state.name && names.has(state.name.trim());
  }, [names, state.name]);

  const validate = useCallback(
    (data) => {
      return !!Object.keys(schema.properties)
        .filter((prop) => !INTERNALS.includes(prop))
        .reduce((isValid, prop) => {
          if (!isValid) return isValid;

          if (!schema.required.includes(prop)) {
            return isValid;
          }

          const value = data[prop];

          if (schema.properties[prop].pattern) {
            isValid =
              isValid &&
              new RegExp(schema.properties[prop].pattern).test(value);
          }

          return (
            isValid && (value || isset(schema.properties[prop], "default"))
          );
        }, true);
    },
    [schema]
  );

  const isValid = useMemo(() => {
    return validate(state);
  }, [validate, state, nameConflict]);

  const frozen = useMemo(() => {
    return !!data.refresh_token;
  }, [data]);

  const timeout = useRef();
  useEffect(() => {
    clearTimeout(timeout.current);

    if (isValid) {
      if (data.name !== state.name) {
        timeout.current = setTimeout(() => {
          name.current = state.name;
          update({ ...state });
        }, 1e3);
      } else if (diff(data, state)) {
        update({ ...state });
      }
    }
  }, [isValid, state]);

  useEffect(() => {
    if (data.name !== name.current) {
      name.current = data.name;
      setState(data);
    }
  }, [data.name]);

  const reloaded = useRef(false);
  useEffect(() => {
    if (!loading && reloaded.current && diff(data, state)) {
      setState(data);
    }

    return () => {
      reloaded.current = loading;
    };
  }, [loading, data, state]);

  const exportConfig = () => {
    const credentialData = { ...data };
    INTERNALS.forEach((prop) => delete credentialData[prop]);
    downloadJson(credentialData, credentialData.name + " credential config");
  };

  const revoke = () => {
    setLoading(true);

    apiFetch({
      path: "http-bridge/v1/oauth/revoke",
      method: "POST",
      data: { credential: data },
    })
      .then(() => fetchSettings())
      .catch(() => setError(""))
      .finally(() => setLoading(false));
  };

  const authorize = () => {
    if (data.refresh_token) {
      revoke();
      return;
    }

    setLoading(true);

    apiFetch({
      path: "http-bridge/v1/oauth/grant",
      method: "POST",
      data: { credential: data },
    })
      .then(({ success }) => {
        if (!success) throw "error";

        const form = document.createElement("form");
        form.method = "POST";
        // form.action = data.oauth_url;
        form.action =
          data.oauth_url +
          "/auth?" +
          new URLSearchParams({
            client_id: data.client_id,
            scope: data.scope,
            response_type: "code",
            redirect_uri: restUrl("http-bridge/v1/oauth/redirect"),
            access_type: "offline",
            state: btoa(addon),
          }).toString();
        form.target = "_blank";

        //     form.innerHTML = `
        // <input name="client_id" value="${data.client_id}" />
        // <input name="scope" value="${data.scope}" />
        // <input name="response_type" value="code" />
        // <input name="redirect_uri" value="${restUrl("http-bridge/v1/oauth/redirect")}" />
        // <input name="access_type" value="offline" />
        // <input name="state" value="${btoa(addon)}" />
        // `;

        form.style.visibility = "hidden";
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
      })
      .catch(() => setError(""))
      .finally(() => setLoading(false));
  };

  const authorizable = !!schema.properties.refresh_token;

  return (
    <div
      style={{
        padding: "calc(24px) calc(32px)",
        width: "calc(100% - 64px)",
        backgroundColor: "rgb(245, 245, 245)",
        display: "flex",
        flexDirection: isResponsive ? "column" : "row",
        gap: "2rem",
      }}
    >
      <div style={{ display: "flex", flexDirection: "column", gap: "0.5rem" }}>
        <CredentialFields
          disabled={frozen}
          data={state}
          setData={setState}
          schema={schema}
          schemas={schemas}
          errors={{
            name: nameConflict
              ? __("This name is already in use", "forms-bridge")
              : false,
          }}
        />
        <div
          style={{
            marginTop: "0.5rem",
            display: "flex",
            gap: "0.5rem",
          }}
        >
          <RemoveButton
            label={__("Delete", "forms-bridge")}
            onClick={() => remove(data)}
            icon
          />
          <Button
            variant="tertiary"
            style={{
              height: "40px",
              width: "40px",
              justifyContent: "center",
              fontSize: "1.5em",
              border: "1px solid",
              padding: "6px 6px",
            }}
            onClick={copy}
            label={__("Duplaicate", "forms-bridge")}
            showTooltip
            __next40pxDefaultSize
          >
            <CopyIcon
              width="25"
              height="25"
              color="var(--wp-components-color-accent,var(--wp-admin-theme-color,#3858e9))"
            />
          </Button>
          <Button
            size="compact"
            variant="tertiary"
            style={{
              height: "40px",
              width: "40px",
              justifyContent: "center",
              fontSize: "1.5em",
              border: "1px solid",
              color: "gray",
            }}
            onClick={exportConfig}
            __next40pxDefaultSize
            label={__("Download bridge config", "forms-bridge")}
            showTooltip
          >
            <ArrowDownIcon width="12" height="20" color="gray" />
          </Button>
          {(authorizable && (
            <Button
              onClick={authorize}
              variant={data.refresh_token ? "secondary" : "primary"}
              isDestructive={!!data.refresh_token}
              disabled={loading || error}
              style={{
                justifyContent: "center",
                marginLeft: "auto",
              }}
              __next40pxDefaultSize
              __nextHasNoMarginBottom
            >
              {data.refresh_token
                ? __("Revoke", "forms-bridge")
                : __("Authorize", "forms-bridge")}
            </Button>
          )) ||
            null}
        </div>
      </div>
    </div>
  );
}
