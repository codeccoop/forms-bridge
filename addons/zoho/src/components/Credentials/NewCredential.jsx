// source
import { useGeneral } from "../../../../../src/providers/Settings";
import useCredentialNames from "../../hooks/useCredentialNames";

const {
  TextControl,
  SelectControl,
  Button,
  __experimentalSpacer: Spacer,
} = wp.components;
const { useState } = wp.element;
const { __ } = wp.i18n;

export default function NewCredential({ add }) {
  const [{ backends }] = useGeneral();
  const backendOptions = [{ label: "", value: "" }].concat(
    backends.map(({ name }) => ({
      label: name,
      value: name,
    }))
  );

  const credentialNames = useCredentialNames();

  const [name, setName] = useState("");
  const [backend, setBackend] = useState("");
  const [nameConflict, setNameConflict] = useState(false);
  const [organizationId, setOrganizationId] = useState("");
  const [clientId, setClientId] = useState("");
  const [clientSecret, setClientSecret] = useState("");

  const handleSetName = (name) => {
    setNameConflict(credentialNames.has(name.trim()));
    setName(name);
  };

  const onClick = () => {
    add({
      name: name.trim(),
      backend,
      organization_id: organizationId,
      client_id: clientId,
      client_secret: clientSecret,
    });

    setName("");
    setBackend("");
    setOrganizationId("");
    setClientId("");
    setClientSecret("");
    setNameConflict(false);
  };

  const disabled = !(
    name &&
    backend &&
    organizationId &&
    clientId &&
    clientSecret &&
    !nameConflict
  );

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
            value={backend}
            onChange={setBackend}
            options={backendOptions}
            __nextHasNoMarginBottom
            __next40pxDefaultSize
          />
        </div>
        <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
          <TextControl
            label={__("Organization ID", "forms-bridge")}
            value={organizationId}
            onChange={setOrganizationId}
            __nextHasNoMarginBottom
            __next40pxDefaultSize
          />
        </div>
        <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
          <TextControl
            label={__("Client ID", "forms-bridge")}
            value={clientId}
            onChange={setClientId}
            __nextHasNoMarginBottom
            __next40pxDefaultSize
          />
        </div>
        <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
          <TextControl
            label={__("Client secret", "forms-bridge")}
            value={clientSecret}
            onChange={setClientSecret}
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
          variant="primary"
          onClick={() => onClick()}
          style={{ width: "150px", justifyContent: "center" }}
          disabled={disabled}
          __next40pxDefaultSize
        >
          {__("Add", "forms-bridge")}
        </Button>
      </div>
    </div>
  );
}
