// source
import useCredentialNames from "../../hooks/useCredentialNames";
import NewCredential from "./NewCredential";

const { TextControl, Button, __experimentalSpacer: Spacer } = wp.components;
const { useState, useEffect, useMemo } = wp.element;
const { __ } = wp.i18n;

export default function Credential({ data, update, remove }) {
  if (data.name === "add") return <NewCredential add={update} />;

  const [name, setName] = useState(data.name);

  const names = useCredentialNames();
  const nameConflict = useMemo(
    () => data.name !== name.trim() && names.has(name.trim()),
    [names, name]
  );

  useEffect(() => {
    if (name && !nameConflict) update({ ...data, name });
  }, [name, nameConflict]);

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
            onChange={setName}
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
