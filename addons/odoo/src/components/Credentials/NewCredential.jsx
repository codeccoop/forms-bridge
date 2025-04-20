// source
import useCredentialNames from "../../hooks/useCredentialNames";

const { TextControl, Button, __experimentalSpacer: Spacer } = wp.components;
const { useState, useMemo } = wp.element;
const { __ } = wp.i18n;

export default function NewCredential({ add }) {
  const names = useCredentialNames();

  const [name, setName] = useState("");
  const [database, setDatabase] = useState("");
  const [user, setUser] = useState("");
  const [password, setPassword] = useState("");

  const nameConflict = useMemo(() => names.has(name.trim()), [name, names]);

  const onClick = () => {
    add({
      name: name.trim(),
      database,
      user,
      password,
    });

    setName("");
    setDatabase("");
    setUser("");
    setPassword("");
  };

  const disabled = !(name && database && user && password && !nameConflict);

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
            label={__("Database", "forms-bridge")}
            value={database}
            onChange={setDatabase}
            __nextHasNoMarginBottom
            __next40pxDefaultSize
          />
        </div>
        <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
          <TextControl
            label={__("User email", "forms-bridge")}
            value={user}
            onChange={setUser}
            __nextHasNoMarginBottom
            __next40pxDefaultSize
          />
        </div>
        <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
          <TextControl
            type="password"
            label={__("Password", "forms-bridge")}
            value={password}
            onChange={setPassword}
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
