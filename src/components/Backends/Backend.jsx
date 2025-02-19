// source
import useBackendNames from "../../hooks/useBackendNames";
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
      </div>
    </div>
  );
}

export default function Backend({ update, remove, ...data }) {
  if (data.name === "add") return <NewBackend add={update} />;

  const [name, setName] = useState(data.name);
  const initialName = useRef(data.name);
  const nameInput = useRef();

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
    timeout.current = setTimeout(
      () => update({ ...data, name: name.trim() }),
      500
    );
  }, [name]);

  useEffect(() => setName(data.name), [data.name]);

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
          ref={nameInput}
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
        <Button
          isDestructive
          variant="primary"
          onClick={() => remove(data)}
          style={{
            width: "150px",
            marginTop: "auto",
            justifyContent: "center",
          }}
          __next40pxDefaultSize
        >
          {__("Remove", "forms-bridge")}
        </Button>
      </div>
      <Spacer paddingY="calc(8px)" />
      <BackendHeaders headers={data.headers} setHeaders={setHeaders} />
    </div>
  );
}
