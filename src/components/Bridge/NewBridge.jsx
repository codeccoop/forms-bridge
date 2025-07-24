// source
import WorkflowProvider from "../../providers/Workflow";
import { useError } from "../../providers/Error";
import BridgeFields, { INTERNALS } from "./Fields";
import Templates from "../Templates";
import { isset, uploadJson } from "../../lib/utils";
import useResponsive from "../../hooks/useResponsive";
import BridgePayload from "./Payload";
import { useBackends } from "../../hooks/useHttp";
import ArrowUpIcon from "../icons/ArrowUp";

const { Button } = wp.components;
const { useState, useEffect, useMemo, useRef, useCallback } = wp.element;
const { __ } = wp.i18n;

export default function NewBridge({ add, schema, names }) {
  const isResponsive = useResponsive();

  const [data, setData] = useState({});

  const [error, setError] = useError();

  const nameConflict = useMemo(() => {
    if (!data.name) return false;
    return names.has(data.name.trim());
  }, [names, data.name]);

  const [backends] = useBackends();
  const includeFiles = useMemo(() => {
    const headers =
      backends.find(({ name }) => name === data.backend)?.headers || [];
    const contentType = headers.find(
      (header) => header.name === "Content-Type"
    )?.value;
    return contentType !== undefined && contentType !== "multipart/form-data";
  }, [backends, data.backend]);

  const create = () => {
    window.__wpfbInvalidated = true;

    setData({});
    add({ ...data, name: data.name.trim() });
  };

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
            isValid = new RegExp(schema.properties[prop].pattern).test(value);
          }

          return (
            isValid && (value || isset(schema.properties[prop], "default"))
          );
        }, true);
    },
    [schema]
  );

  const isValid = useMemo(() => {
    return validate(data);
  }, [data]);

  const uploadConfig = useCallback(() => {
    uploadJson()
      .then((data) => {
        const isValid = validate(data);

        if (!isValid) {
          setError(__("Invalid bridge config", "forms-bridge"));
          return;
        }

        let i = 1;
        while (names.has(data.name)) {
          data.name = data.name.replace(/ \([0-9]+\)/, "") + ` (${i})`;
          i++;
        }

        add(data);
      })
      .catch((err) => {
        if (err.name === "SyntaxError") {
          setError(__("JSON syntax error", "forms-bridge"));
        } else {
          setError(
            __(
              "An error has ocurred while uploading the bridge config",
              "forms-bridge"
            )
          );
        }
      });
  }, [names]);

  const fieldsRef = useRef();
  const [height, setHeight] = useState(0);
  useEffect(() => {
    setHeight(0);
    setTimeout(() => setHeight(fieldsRef.current.offsetHeight), 100);
  }, [schema]);

  return (
    <WorkflowProvider
      formId={data.form_id}
      mutations={[]}
      workflow={[]}
      customFields={[]}
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
            data={data}
            setData={setData}
            schema={schema}
            optionals={true}
            errors={{
              name: nameConflict
                ? __("This name is already in use", "forms-bridge")
                : false,
            }}
          />
          <div style={{ marginTop: "0.5rem", display: "flex", gap: "0.5rem" }}>
            <Button
              variant="primary"
              onClick={create}
              style={{ width: "100px", justifyContent: "center" }}
              disabled={nameConflict || !isValid}
              __next40pxDefaultSize
            >
              {__("Add", "forms-bridge")}
            </Button>
            <Button
              variant="tertiary"
              size="compact"
              style={{
                width: "40px",
                height: "40px",
                justifyContent: "center",
                fontSize: "1.5em",
                border: "1px solid",
                color: "grey",
              }}
              disabled={!!error}
              onClick={uploadConfig}
              __next40pxDefaultSize
              label={__("Upload", "forms-bridge")}
              showTooltip
            >
              <ArrowUpIcon width="12" height="20" color="gray" />
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
                  display: "flex",
                  flexDirection: "column",
                  flex: 1,
                }
          }
        >
          <BridgePayload height={height} />
          <div
            style={{
              paddingTop: "16px",
              display: "flex",
              gap: "0.5rem",
              flexDirection: "column",
              borderTop: "1px solid",
            }}
          >
            <Templates />
          </div>
        </div>
      </div>
    </WorkflowProvider>
  );
}
