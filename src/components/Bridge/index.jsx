// source
import useBridgeNames from "../../hooks/useBridgeNames";
import CustomFields from "../CustomFields";
import Workflow from "../Workflow";
import NewBridge from "./NewBridge";
import RemoveButton from "../RemoveButton";
import { downloadJson } from "../../lib/utils";
import BridgeFields, { INTERNALS } from "./Fields";
import ToggleControl from "../Toggle";

const { Button } = wp.components;
const { useState, useEffect, useMemo, useCallback } = wp.element;
const { __ } = wp.i18n;

export default function Bridge({ data, update, remove, schema }) {
  const [state, setState] = useState({ ...data });

  const names = useBridgeNames();

  const nameConflict = useMemo(() => {
    if (!state.name) return false;
    return state.name.trim() !== data.name && names.has(state.name.trim());
  }, [names, state.name]);

  const validate = useCallback(
    (data) => {
      return !!Object.keys(schema.properties)
        .filter((prop) => !INTERNALS.includes(prop))
        .reduce((isValid, prop) => {
          if (!isValid) return isValid;

          const value = data[prop];

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

  const isValid = useMemo(
    () => validate(state) && !nameConflict,
    [state, nameConflict]
  );

  if (!isValid && state.is_valid) {
    setState({ ...state, is_valid: false });
    update({ ...state, is_valid: false });
  }

  useEffect(() => {
    if (isValid) update({ ...state, is_valid: true });
  }, [isValid, state]);

  useEffect(() => {
    setState(data);
  }, [data.name]);

  const exportConfig = () => {
    const bridgeData = { ...data };
    delete bridgeData.is_valid;

    downloadJson(bridgeData, bridgeData.name + " bridge config");
  };

  return (
    <div
      style={{
        padding: "calc(24px) calc(32px)",
        width: "calc(100% - 64px)",
        backgroundColor: "rgb(245, 245, 245)",
      }}
    >
      <div style={{ display: "flex", gap: "2rem" }}>
        <div style={{ flex: 1 }}>
          <div
            style={{
              display: "flex",
              gap: "0.5rem",
              flexWrap: "wrap",
            }}
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
          </div>
          <div style={{ marginTop: "10px", display: "flex", gap: "0.5rem" }}>
            <RemoveButton
              onClick={() => remove(data)}
              style={{ width: "100px" }}
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
            <div
              style={{
                marginLeft: "15px",
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
              }}
            >
              <ToggleControl
                disabled={!isValid}
                checked={state.enabled && isValid}
                onChange={() => setState({ ...state, enabled: !state.enabled })}
                __nextHasNoMarginBottom
              />
              {(!isValid || !state.enabled) && (
                <span
                  style={{
                    marginLeft: "-5px",
                    fontStyle: "normal",
                    fontSize: "12px",
                    color: "rgb(117, 117, 117)",
                  }}
                >
                  {__("Disabled", "forms-bridge")}
                </span>
              )}
            </div>
          </div>
        </div>
        <div
          style={{
            marginTop: "23px",
            paddingLeft: "2rem",
            borderLeft: "1px solid",
          }}
        >
          <div
            style={{ display: "flex", gap: "0.5rem", flexDirection: "column" }}
          >
            <CustomFields
              customFields={state.custom_fields || []}
              setCustomFields={(custom_fields) =>
                setState({
                  ...state,
                  custom_fields,
                })
              }
            />
            <Workflow
              backend={state.backend}
              formId={state.form_id}
              customFields={state.custom_fields}
              mutations={state.mutations}
              workflow={state.workflow}
              setWorkflow={(workflow) => setState({ ...state, workflow })}
              setMutationMappers={(mutation, mappers) => {
                setState({
                  ...state,
                  mutations: state.mutations
                    .slice(0, mutation)
                    .concat([mappers])
                    .concat(state.mutations.slice(mutation + 1)),
                });
              }}
            />
          </div>
        </div>
      </div>
    </div>
  );
}

Bridge.NewBridge = NewBridge;
