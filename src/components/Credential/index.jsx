// source
import RemoveButton from "../RemoveButton";
import { useCredentials } from "../../hooks/useAddon";
import CredentialFields, { INTERNALS } from "./Fields";
import ToggleControl from "../Toggle";
import { downloadJson } from "../../lib/utils";
import { useLoading } from "../../providers/Loading";
import { useError } from "../../providers/Error";
import diff from "../../lib/diff";
import useResponsive from "../../hooks/useResponsive";
import CopyIcon from "../icons/Copy";

const { Button } = wp.components;
const { useState, useEffect, useMemo, useRef, useCallback } = wp.element;
const apiFetch = wp.apiFetch;
const { __ } = wp.i18n;

export default function Credential({
  addon,
  data,
  update,
  remove,
  schema,
  copy,
}) {
  const isResponsive = useResponsive(780);

  const [loading, setLoading] = useLoading();
  const [error, setError] = useError();

  const name = useRef(data.name);
  const [state, setState] = useState({ ...data });

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

          const value = data[prop];

          if (!schema.required.includes(prop)) {
            return isValid;
          }

          if (schema.properties[prop].pattern) {
            isValid =
              isValid &&
              new RegExp(schema.properties[prop].pattern).test(value);
          }

          return isValid && value;
        }, true);
    },
    [schema]
  );

  const isValid = useMemo(() => {
    return validate(state);
  }, [state, nameConflict]);

  if (!isValid && state.is_valid) {
    setState({ ...state, is_valid: false });
    update({ ...state, is_valid: false });
  }

  const frozen = useMemo(() => {
    return !!data.access_token;
  }, [data]);

  const timeout = useRef();
  useEffect(() => {
    clearTimeout(timeout.current);

    if (isValid) {
      timeout.current = setTimeout(
        () => {
          name.current = state.name;
          update({ ...state, is_valid: isValid });
        },
        (data.name !== state.name && 1e3) || 0
      );
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
    if (reloaded.current && diff(data, state)) {
      setState(data);
    }

    return () => {
      reloaded.current = loading;
    };
  }, [loading]);

  const exportConfig = () => {
    const credentialData = { ...data };
    INTERNALS.forEach((prop) => delete credentialData[prop]);
    downloadJson(credentialData, credentialData.name + " credential config");
  };

  const authorize = () => {
    setLoading(true);

    apiFetch({
      path: `forms-bridge/v1/${addon}/oauth/grant`,
      method: "POST",
      data: { credential: data },
    })
      .then(({ redirect }) => {
        if (redirect === window.location.href) {
          window.location.reload();
        } else {
          window.open(redirect);
        }
      })
      .catch(() => {
        setError(__("Error while authorizing credential", "forms-bridge"));
      })
      .finally(() => setLoading(false));
  };

  const authorizable = !!schema.properties.access_token;

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
            ⬇
          </Button>
        </div>
      </div>
      <div
        style={
          isResponsive
            ? {
                paddingTop: "2rem",
                borderTop: "1px solid",
                display: "flex",
                flexDirection: "column",
                gap: "1rem",
              }
            : {
                paddingLeft: "2rem",
                borderLeft: "1px solid",
                display: "flex",
                flexDirection: "column",
                flex: 1,
                gap: "1rem",
              }
        }
      >
        <div
          style={{
            display: "flex",
            alignItems: "center",
            marginTop: "10px",
            marginLeft: "4px",
          }}
        >
          <ToggleControl
            disabled={true}
            checked={isValid}
            noEdit
            __nextHasNoMarginBottom
          />
          <span
            style={{
              width: "50px",
              fontStyle: "normal",
              fontSize: "12px",
              color: isValid
                ? "var(--wp-components-color-accent,var(--wp-admin-theme-color,#3858e9))"
                : "rgb(117, 117, 117)",
            }}
          >
            {!isValid
              ? __("Invalid", "forms-bridge")
              : __("Valid", "forms-bridge")}
          </span>
        </div>
        <Button
          onClick={authorize}
          variant={data.access_token || !authorizable ? "secondary" : "primary"}
          isDestructive={!!data.access_token}
          disabled={!authorizable || loading || error}
          style={{
            justifyContent: "center",
            width: "100px",
          }}
          __next40pxDefaultSize
          __nextHasNoMarginBottom
        >
          {data.access_token
            ? __("Revoke", "forms-bridge")
            : __("Authorize", "forms-bridge")}
        </Button>
      </div>
    </div>
  );
}
