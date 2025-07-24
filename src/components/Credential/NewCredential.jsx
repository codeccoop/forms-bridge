// source
import { useCredentials } from "../../hooks/useHttp";
import { isset, uploadJson } from "../../lib/utils";
import { useError } from "../../providers/Error";
import CredentialFields, { INTERNALS } from "./Fields";
import useResponsive from "../../hooks/useResponsive";
import ArrowUpIcon from "../icons/ArrowUp";

const { Button } = wp.components;
const { useState, useMemo, useCallback } = wp.element;
const { __ } = wp.i18n;

export default function NewCredential({ add, schema: schemas }) {
  const isResponsive = useResponsive(780);

  const [error, setError] = useError();

  const [data, setData] = useState({ schema: "Basic" });

  const schema = useMemo(() => {
    return schemas.oneOf.find(
      (schema) =>
        schema.properties.schema.const === data.schema ||
        schema.properties.schema.enum?.includes(data.schema)
    );
  }, [data.schema]);

  const [credentials] = useCredentials();
  const names = useMemo(() => {
    return new Set(credentials.map((c) => c.name));
  }, [credentials]);

  const nameConflict = useMemo(() => {
    if (!data.name) return false;
    return names.has(data.name.trim());
  }, [names, data.name]);

  const create = () => {
    const credential = { ...data, name: data.name.trim() };
    Object.keys(schema.properties).forEach((prop) => {
      if (schema.required.includes(prop) && !credential[prop]) {
        credential[prop] = schema.properties[prop].default;
      }
    });

    window.__wpfbInvalidated = true;

    setData({});
    add(credential);
  };

  const validate = useCallback(
    (data) => {
      return !!Object.keys(schema.properties)
        .filter((prop) => !INTERNALS.includes(prop))
        .filter((prop) => !["access_token", "expires_at"].includes(prop))
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
    return validate(data);
  }, [data]);

  const uploadConfig = useCallback(() => {
    uploadJson()
      .then((data) => {
        const isValid = validate(data);

        if (!isValid) {
          setError(__("Invalid credential config", "forms-bridge"));
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
              "An error has ocurred while uploading the credential config",
              "forms-bridge"
            )
          );
        }
      });
  }, [names]);

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
          data={data}
          setData={setData}
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
          <Button
            variant="primary"
            onClick={create}
            style={{ width: "100px", justifyContent: "center" }}
            disabled={!isValid || nameConflict}
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
    </div>
  );
}
