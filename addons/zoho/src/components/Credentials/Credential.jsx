// source
import { useGeneral } from "../../../../../src/providers/Settings";
import useCredentialNames from "../../hooks/useCredentialNames";
import NewCredential from "./NewCredential";

const {
  TextControl,
  SelectControl,
  Button,
  __experimentalSpacer: Spacer,
} = wp.components;
const { useState, useRef, useEffect } = wp.element;
const { __ } = wp.i18n;

export default function Credential({ data, update, remove }) {
  if (data.name === "add") return <NewCredential add={update} />;

  const [{ backends }] = useGeneral();
  const backendOptions = [{ label: "", value: "" }].concat(
    backends.map(({ name }) => ({
      label: name,
      value: name,
    }))
  );

  const [name, setName] = useState(data.name);
  const initialName = useRef(data.name);

  const credentialNames = useCredentialNames();
  const [nameConflict, setNameConflict] = useState(false);
  const handleSetName = (name) => {
    setNameConflict(
      name !== initialName.current && credentialNames.has(name.trim())
    );
    setName(name);
  };

  const timeout = useRef();
  useEffect(() => {
    clearTimeout(timeout.current);
    if (!name || nameConflict) return;
    timeout.current = setTimeout(() => {
      if (credentialNames.has(name.trim())) return;
      update({ ...data, name: name.trim() });
    }, 500);
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
          flexWrap: "wrap",
        }}
      >
        <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
          <TextControl
            label={__("Name", "forms-bridge")}
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
        </div>
        <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
          <SelectControl
            label={__("Backend", "forms-bridge")}
            value={data.backend}
            onChange={(backend) => update({ ...data, backend })}
            options={backendOptions}
            __nextHasNoMarginBottom
            __next40pxDefaultSize
          />
        </div>
        <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
          <TextControl
            label={__("Organization ID", "forms-bridge")}
            value={data.organization_id}
            onChange={(organization_id) => update({ ...data, organization_id })}
            __nextHasNoMarginBottom
            __next40pxDefaultSize
          />
        </div>
        <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
          <TextControl
            label={__("Client ID", "forms-bridge")}
            value={data.client_id}
            onChange={(client_id) => update({ ...data, client_id })}
            __nextHasNoMarginBottom
            __next40pxDefaultSize
          />
        </div>
        <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
          <TextControl
            label={__("Client secret", "forms-bridge")}
            value={data.client_secret}
            onChange={(client_secret) => update({ ...data, client_secret })}
            __nextHasNoMarginBottom
            __next40pxDefaultSize
          />
        </div>
      </div>
      <Spacer paddingY="calc(8px)" />
      <div
        style={{
          display: "flex",
          gap: "0.5rem",
        }}
      >
        <Button
          isDestructive
          variant="primary"
          onClick={() => remove(data)}
          style={{ width: "150px", justifyContent: "center" }}
          __next40pxDefaultSize
        >
          {__("Remove", "forms-bridge")}
        </Button>
      </div>
    </div>
  );
}
