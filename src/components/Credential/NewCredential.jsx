// source
import { useCredentials } from "../../hooks/useAddon";
import { uploadJson } from "../../lib/utils";
import { useError } from "../../providers/Error";
import CredentialFields, { INTERNALS } from "./Fields";

const { Button, __experimentalSpacer: Spacer } = wp.components;
const { useState, useMemo, useCallback } = wp.element;
const { __ } = wp.i18n;

export default function NewCredential({ add, schema }) {
  const [error, setError] = useError();

  const [data, setData] = useState({});

  const [credentials] = useCredentials();
  const names = useMemo(() => {
    return new Set(credentials.map((c) => c.name));
  }, [credentials]);

  const nameConflict = useMemo(() => {
    if (!data.name) return false;
    return names.has(data.name.trim());
  }, [names, data.name]);

  const create = () => {
    setData({});
    add({ ...data, name: data.name.trim() });
  };

  const validate = useCallback(
    (data) => {
      return Object.keys(schema.properties)
        .filter((prop) => !INTERNALS.includes(prop))
        .reduce((isValid, prop) => {
          const value = data[prop];
          if (schema.properties[prop].pattern) {
            isValid = new RegExp(schema.properties[prop].pattern).test(value);
          }

          return isValid && value;
        });
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
            <CredentialFields
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
          </div>
          <Spacer />
          <div
            style={{
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
              label={__("Upload credential config", "forms-bridge")}
              showTooltip
            >
              ⬆
            </Button>
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
          ></div>
        </div>
      </div>
    </div>
  );
}
