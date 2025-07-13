// source
import useBackendNames from "../../hooks/useBackendNames";
import RemoveButton from "../RemoveButton";
import BackendHeaders from "./Headers";
import BackendAuthentication from "./Authentication";
import { downloadJson } from "../../lib/utils";

const { TextControl, Button, __experimentalSpacer: Spacer } = wp.components;
const { useState, useRef, useEffect, useMemo } = wp.element;
const { __ } = wp.i18n;

export default function Backend({ update, remove, data }) {
  const [state, setState] = useState({ ...data });

  const backendNames = useBackendNames();

  const nameConflict = useMemo(() => {
    if (!state.name) return false;
    return (
      state.name.trim() !== data.name && backendNames.has(state.name.trim())
    );
  }, [backendNames, state.name]);

  const validate = useRef((data) => {
    return data.name && data.base_url && Array.isArray(data.headers);
  }).current;

  const isValid = useMemo(
    () => validate(state) && !nameConflict,
    [state, nameConflict]
  );

  useEffect(() => {
    if (isValid) update(state);
  }, [isValid, state]);

  useEffect(() => {
    setState(data);
  }, [data.name]);

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
      }}
    >
      <div
        style={{
          display: "flex",
          gap: "0.5rem",
        }}
      >
        <TextControl
          style={{ minWidth: "250px" }}
          label={__("Backend name", "forms-bridge")}
          help={
            nameConflict
              ? __("This name is already in use", "forms-bridge")
              : ""
          }
          value={state.name}
          onChange={(name) => setState({ ...state, name })}
          __nextHasNoMarginBottom
          __next40pxDefaultSize
        />
        <TextControl
          style={{ minWidth: "350px" }}
          label={__("Backend base URL", "forms-bridge")}
          value={state.base_url}
          onChange={(base_url) => setState({ ...state, base_url })}
          __nextHasNoMarginBottom
          __next40pxDefaultSize
        />
      </div>
      <Spacer paddingY="calc(4px)" />
      <BackendHeaders
        headers={state.headers}
        setHeaders={(headers) => setState({ ...state, headers })}
      />
      <Spacer paddingY="calc(4px)" />
      <BackendAuthentication
        data={data.authentication}
        setData={(authentication) => setState({ ...state, authentication })}
      />
      <Spacer paddingY="calc(4px)" />
      <div
        style={{
          display: "flex",
          gap: "0.5rem",
          flexWrap: "wrap",
        }}
      >
        <RemoveButton
          onClick={() => remove(data)}
          style={{
            width: "100px",
            marginTop: "auto",
            justifyContent: "center",
          }}
        >
          {__("Remove", "forms-bridge")}
        </RemoveButton>
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
  );
}
