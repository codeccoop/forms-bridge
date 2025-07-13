import { useError } from "../../providers/Error";
import useBackendNames from "../../hooks/useBackendNames";
import { uploadJson } from "../../lib/utils";

const { TextControl, Button, __experimentalSpacer: Spacer } = wp.components;
const { useState, useMemo } = wp.element;
const { __ } = wp.i18n;

export default function NewBackend({ add }) {
  const backendNames = useBackendNames();

  const [name, setName] = useState("");
  const [baseUrl, setBaseUrl] = useState("https://");

  const [error, setError] = useError();

  const nameConflict = useMemo(() => {
    if (!name) return false;
    return backendNames.has(name);
  }, [backendNames, name]);

  const create = () => {
    add({
      name: name.trim(),
      base_url: baseUrl,
      headers: [{ name: "Content-Type", value: "application/json" }],
    });
    setName("");
    setBaseUrl("https://");
  };

  function uploadConfig() {
    uploadJson()
      .then((data) => {
        const isValid = data.name && data.base_url;

        if (!isValid) {
          setError(__("Invalid backend config", "forms-bridge"));
          return;
        }

        let i = 1;
        while (backendNames.has(data.name)) {
          data.name = data.name.replace(/\([0-9]+\)/, "") + ` (${i})`;
          i++;
        }

        data.headers =
          (Array.isArray(data.headers) &&
            data.headers.filter(
              (header) => header && header.name && header.value
            )) ||
          [];

        add(data);
      })
      .catch((err) => {
        if (!err) return;

        console.error(err);
        setError(
          __(
            "An error has ocurred while uploading the backend config",
            "forms-bridge"
          )
        );
      });
  }

  const disabled = !(name && baseUrl && !nameConflict);

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
          value={name}
          onChange={setName}
          __nextHasNoMarginBottom
          __next40pxDefaultSize
        />
        <TextControl
          style={{ minWidth: "350px" }}
          label={__("Backend base URL", "forms-bridge")}
          value={baseUrl}
          onChange={setBaseUrl}
          __nextHasNoMarginBottom
          __next40pxDefaultSize
        />
      </div>
      <Spacer />
      <div
        style={{
          display: "flex",
          gap: "0.5rem",
          flexWrap: "wrap",
        }}
      >
        <Button
          variant="primary"
          onClick={create}
          style={{
            width: "100px",
            justifyContent: "center",
            marginTop: "auto",
          }}
          disabled={disabled}
          __next40pxDefaultSize
        >
          {__("Add", "forms-bridge")}
        </Button>
        <Button
          disabled={!!error}
          variant="tertiary"
          size="compact"
          style={{
            width: "40px",
            height: "40px",
            justifyContent: "center",
            fontSize: "1.5em",
            border: "1px solid",
            color: "gray",
          }}
          onClick={uploadConfig}
          __next40pxDefaultSize
          label={__("Upload backend config", "forms-bridge")}
          showTooltip
        >
          ⬆
        </Button>
      </div>
    </div>
  );
}
