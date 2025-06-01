// source
import useBackendNames from "../../hooks/useBackendNames";
import { downloadJson, uploadJson } from "../../lib/utils";
import RemoveButton from "../RemoveButton";
import BackendHeaders from "./Headers";

const { TextControl, Button, __experimentalSpacer: Spacer } = wp.components;
const { useState, useRef, useEffect } = wp.element;
const { __ } = wp.i18n;

function NewBackend({ add }) {
  const backendNames = useBackendNames();

  const [name, setName] = useState("");
  const [baseUrl, setBaseUrl] = useState("https://");
  const [nameConflict, setNameConflict] = useState(false);

  const handleSetName = (name) => {
    setNameConflict(backendNames.has(name.trim()));
    setName(name);
  };

  const onClick = () => {
    add({ name: name.trim(), base_url: baseUrl, headers: [] });
    setName("");
    setBaseUrl("https://");
    setNameConflict(false);
  };

  function uploadConfig() {
    uploadJson()
      .then((data) => {
        const isValid = data.name && data.base_url;

        if (!isValid) {
          wpfb.emit("error", __("Invalid backend config", "forms-bridge"));
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
        wpfb.emit(
          "error",
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
          gap: "1em",
        }}
      >
        <TextControl
          label={__("Backend name", "forms-bridge")}
          help={
            nameConflict
              ? __("This name is already in use", "forms-bridge")
              : ""
          }
          value={name}
          onChange={handleSetName}
          __nextHasNoMarginBottom
          __next40pxDefaultSize
        />
        <TextControl
          style={{ minWidth: "300px" }}
          label={__("Backend base URL", "forms-bridge")}
          value={baseUrl}
          onChange={setBaseUrl}
          __nextHasNoMarginBottom
          __next40pxDefaultSize
        />
      </div>
      <Spacer paddingY="calc(8px)" />
      <div
        style={{
          display: "flex",
          gap: "1em",
          flexWrap: "wrap",
        }}
      >
        <Button
          variant="primary"
          onClick={() => onClick()}
          style={{
            width: "150px",
            justifyContent: "center",
            marginTop: "auto",
          }}
          disabled={disabled}
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

export default function Backend({ update, remove, ...data }) {
  if (data.name === "add") return <NewBackend add={update} />;

  const [name, setName] = useState(data.name);
  const initialName = useRef(data.name);

  const backendNames = useBackendNames();
  const [nameConflict, setNameConflict] = useState(false);
  const handleSetName = (name) => {
    setNameConflict(
      name.trim() !== initialName.current && backendNames.has(name.trim())
    );
    setName(name);
  };

  const setHeaders = (headers) => update({ ...data, headers });

  const timeout = useRef(false);
  useEffect(() => {
    clearTimeout(timeout.current);
    if (!name || nameConflict) return;
    timeout.current = setTimeout(() => {
      if (backendNames.has(name.trim())) return;
      update({ ...data, name: name.trim() });
    }, 500);
  }, [name]);

  useEffect(() => setName(data.name), [data.name]);

  function exportConfig() {
    const backendData = { ...data };
    delete backendData.icon;
    delete backendData.title;

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
          gap: "1em",
        }}
      >
        <TextControl
          label={__("Backend name", "forms-bridge")}
          help={
            nameConflict
              ? __("This name is already in use", "forms-bridge")
              : ""
          }
          value={name}
          onChange={handleSetName}
          __nextHasNoMarginBottom
          __next40pxDefaultSize
        />
        <TextControl
          style={{ minWidth: "300px" }}
          label={__("Backend base URL", "forms-bridge")}
          value={data.base_url}
          onChange={(base_url) => update({ ...data, base_url })}
          __nextHasNoMarginBottom
          __next40pxDefaultSize
        />
      </div>
      <Spacer paddingY="calc(8px)" />
      <BackendHeaders headers={data.headers} setHeaders={setHeaders} />
      <Spacer paddingY="calc(8px)" />
      <div
        style={{
          display: "flex",
          gap: "1em",
          flexWrap: "wrap",
        }}
      >
        <RemoveButton
          onClick={() => remove(data)}
          style={{
            width: "150px",
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
