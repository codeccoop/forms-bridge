// source
import WorkflowProvider from "../../providers/Workflow";
import CustomFields from "../CustomFields";
import Workflow from "../Workflow";
import RemoveButton from "../RemoveButton";
import { isset, downloadJson } from "../../lib/utils";
import BridgeFields, { INTERNALS } from "./Fields";
import ToggleControl from "../Toggle";
import useResponsive from "../../hooks/useResponsive";
import CopyIcon from "../icons/Copy";
import ArrowDownIcon from "../icons/ArrowDown";
import diff from "../../lib/diff";
import { useLoading } from "../../providers/Loading";
import BridgePayload from "./Payload";
import Mutations from "../Mutations";
import useBackends from "../../hooks/useBackends";
import { useError } from "../../providers/Error";
import { useCredentials } from "../../hooks/useAddon";
import useTab from "../../hooks/useTab";

const { Button } = wp.components;
const { useState, useEffect, useMemo, useCallback, useRef } = wp.element;
const apiFetch = wp.apiFetch;
const { __ } = wp.i18n;

export default function Bridge({ data, update, remove, schema, copy, names }) {
  const [addon] = useTab();

  const [loading, setLoading] = useLoading();
  const [error, setError] = useError();
  const isResponsive = useResponsive();

  const name = useRef(data.name);
  const [state, setState] = useState({ ...data });
  const [workflowOpen, setWorkflowOpen] = useState(false);

  const currentState = useRef(state);
  currentState.current = state;

  const patchState = (patch) => setState({ ...currentState.current, ...patch });

  const nameConflict = useMemo(() => {
    if (!state.name) return false;
    if (state.name.trim() === name.current.trim()) return false;
    return state.name !== name.current && names.has(state.name.trim());
  }, [names, state.name]);

  const [backends] = useBackends();
  const backend = useMemo(() => {
    return backends.find(({ name }) => name === state.backend);
  }, [backends, state.backend]);

  const includeFiles = useMemo(() => {
    const headers =
      backends.find(({ name }) => name === state.backend)?.headers || [];
    const contentType = headers.find(
      (header) => header.name === "Content-Type"
    )?.value;
    return contentType !== undefined && contentType !== "multipart/form-data";
  }, [backends, state.backend]);

  const [credentials] = useCredentials();
  const credential = useMemo(() => {
    return credentials
      .filter(({ is_valid }) => is_valid)
      .find(({ name }) => name === state.credential);
  }, [credentials, data.credential]);

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

          return (
            isValid && (value || isset(schema.properties[prop], "default"))
          );
        }, true);
    },
    [schema]
  );

  const isValid = useMemo(
    () => validate(state) && !nameConflict,
    [state, nameConflict]
  );

  if (!isValid && data.is_valid) {
    update({ ...data, is_valid: false });
  }

  const timeout = useRef();
  useEffect(() => {
    clearTimeout(timeout.current);

    if (isValid) {
      if (data.name !== state.name) {
        timeout.current = setTimeout(() => {
          name.current = state.name;
          update({ ...state, is_valid: true });
        }, 1e3);
      } else if (diff(data, state)) {
        update({ ...state, is_valid: true });
      }
    }
  }, [isValid, state]);

  useEffect(() => {
    if (data.name !== name.current) {
      name.current = data.name;
      setState(data);
      setPing(false);
    }
  }, [data.name]);

  const reloaded = useRef(false);
  useEffect(() => {
    if (!loading && reloaded.current && diff(data, state)) {
      setState(data);
      setPing(false);
    }

    return () => {
      reloaded.current = loading;
    };
  }, [loading, data, state]);

  const exportConfig = useCallback(() => {
    const bridgeData = { ...data };
    downloadJson(bridgeData, bridgeData.name + " bridge config");
  }, [data]);

  const fieldsRef = useRef();
  const [height, setHeight] = useState(0);
  useEffect(() => {
    setHeight(0);
    if (!fieldsRef.current) return;
    setTimeout(() => setHeight(fieldsRef.current.offsetHeight), 100);
  }, [schema]);

  const [ping, setPing] = useState(false);

  useEffect(() => {
    if (ping) {
      setPing(false);
    }
  }, [state.backend, state.credential]);

  const doPing = useCallback(() => {
    setLoading(true);

    apiFetch({
      path: `forms-bridge/v1/${addon}/backend/ping`,
      method: "POST",
      data: { backend, credential },
    })
      .then(({ success }) => {
        if (success) setPing(true);
        else setError(__("Backend is unreachable", "forms-bridge"));
      })
      .catch(() => {
        setPing(false);
        setError(__("Backend is unreachable", "forms-bridge"));
      })
      .finally(() => setLoading(false));
  }, [addon, backend, credential]);

  const enabled = isValid && state.enabled;

  return (
    <WorkflowProvider
      formId={state.form_id}
      mutations={state.mutations}
      workflow={state.workflow}
      customFields={state.custom_fields}
      includeFiles={includeFiles}
    >
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
        <div
          ref={fieldsRef}
          style={{ display: "flex", flexDirection: "column", gap: "0.5rem" }}
        >
          <BridgeFields
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
            <Button
              disabled={!!error || loading || ping}
              size="compact"
              variant="primary"
              onClick={doPing}
              style={{
                background: ping
                  ? "#4ab866"
                  : "var(--wp-components-color-accent,var(--wp-admin-theme-color,#3858e9))",
                marginLeft: "auto",
                height: "40px",
                justifyContent: "center",
              }}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            >
              ping
            </Button>
          </div>
        </div>
        <div
          style={
            isResponsive
              ? {}
              : {
                  paddingLeft: "2rem",
                  borderLeft: "1px solid",
                  display: "flex",
                  flexDirection: "column",
                  flex: 1,
                }
          }
        >
          <BridgePayload height={height} focus={!workflowOpen} />
          <div
            style={{
              paddingTop: "16px",
              display: "flex",
              flexDirection: isResponsive ? "column" : "row",
              gap: "0.5rem",
              borderTop: "1px solid",
            }}
          >
            <div style={{ display: "flex", gap: "0.5rem" }}>
              <CustomFields
                customFields={state.custom_fields || []}
                setCustomFields={(custom_fields) =>
                  patchState({
                    custom_fields,
                  })
                }
              />
              <Mutations
                formId={state.form_id}
                customFields={state.custom_fields}
                mappers={state.mutations[0]}
                setMappers={(mappers) =>
                  patchState({
                    mutations: [mappers, ...state.mutations.slice(1)],
                  })
                }
                includeFiles={includeFiles}
              />
              <Workflow
                workflow={state.workflow}
                setWorkflow={(workflow) => patchState({ workflow })}
                setMutationMappers={(mutation, mappers) => {
                  patchState({
                    mutations: state.mutations
                      .slice(0, mutation)
                      .concat([mappers])
                      .concat(state.mutations.slice(mutation + 1)),
                  });
                }}
                open={workflowOpen}
                setOpen={setWorkflowOpen}
              />
            </div>
            <div
              style={{
                marginLeft: isResponsive ? 0 : "auto",
                display: "flex",
                alignItems: "center",
              }}
            >
              <ToggleControl
                disabled={!isValid}
                checked={state.enabled && isValid}
                onChange={() => patchState({ enabled: !state.enabled })}
                __nextHasNoMarginBottom
              />
              <span
                style={{
                  width: "50px",
                  marginLeft: "-10px",
                  fontStyle: "normal",
                  fontSize: "12px",
                  color: enabled
                    ? "var(--wp-components-color-accent,var(--wp-admin-theme-color,#3858e9))"
                    : "rgb(117, 117, 117)",
                }}
              >
                {!isValid || !state.enabled
                  ? __("Disabled", "forms-bridge")
                  : __("Enabled", "forms-bridge")}
              </span>
            </div>
          </div>
        </div>
      </div>
    </WorkflowProvider>
  );
}
