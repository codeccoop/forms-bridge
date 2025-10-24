// source
import useBackendNames from "../../hooks/useBackendNames";
import RemoveButton from "../RemoveButton";
import BackendHeaders from "./Headers";
import { downloadJson, validateUrl, validateBackend } from "../../lib/utils";
import useResponsive from "../../hooks/useResponsive";
import CopyIcon from "../icons/Copy";
import ArrowDownIcon from "../icons/ArrowDown";
import diff from "../../lib/diff";
import { useLoading } from "../../providers/Loading";
import BackendFields from "./Fields";

const { Button } = wp.components;
const { useState, useEffect, useMemo, useRef } = wp.element;
const { __ } = wp.i18n;

export default function Backend({ update, remove, data, copy }) {
  const [loading] = useLoading();
  const isResponsive = useResponsive();

  const name = useRef(data.name);
  const [state, setState] = useState({ ...data });

  const names = useBackendNames();

  const nameConflict = useMemo(() => {
    if (!state.name) return false;
    if (state.name.trim() === name.current.trim()) return false;
    return state.name !== name.current && names.has(state.name.trim());
  }, [names, state.name]);

  const invalidUrl = useMemo(() => {
    return !validateUrl(state.base_url, true);
  }, [state.base_url]);

  const isValid = useMemo(
    () => !nameConflict && !invalidUrl && validateBackend(state),
    [state, nameConflict, invalidUrl]
  );

  const timeout = useRef();
  useEffect(() => {
    clearTimeout(timeout.current);

    if (isValid) {
      if (state.name !== data.name) {
        timeout.current = setTimeout(() => {
          name.current = state.name;
          update(state);
        }, 1e3);
      } else if (diff(state, data)) {
        update(state);
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

  function exportConfig() {
    const backendData = { ...data };
    downloadJson(backendData, data.name + " backend config");
  }

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
        <BackendFields
          state={state}
          setState={setState}
          errors={{
            name: nameConflict,
            base_url: invalidUrl,
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
            label={__("Download", "forms-bridge")}
            showTooltip
            __next40pxDefaultSize
          >
            <ArrowDownIcon width="12" height="20" color="gray" />
          </Button>
        </div>
      </div>
      <div
        style={
          isResponsive
            ? {
                paddingTop: "2rem",
                borderTop: "1px solid",
              }
            : {
                paddingLeft: "2rem",
                borderLeft: "1px solid",
                flex: 1,
              }
        }
      >
        <BackendHeaders
          headers={state.headers}
          setHeaders={(headers) => setState({ ...state, headers })}
        />
      </div>
    </div>
  );
}
