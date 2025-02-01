// vendor
import React from "react";
import {
  TextControl,
  SelectControl,
  Button,
  __experimentalSpacer as Spacer,
} from "@wordpress/components";
import { useState, useMemo } from "@wordpress/element";

// source
import { useGeneral } from "../../../../../src/providers/Settings";

export default function NewDatabase({ add, databases }) {
  const __ = wp.i18n.__;

  const [{ backends }] = useGeneral();
  const backendOptions = [{ label: "", value: "" }].concat(
    backends.map(({ name }) => ({
      label: name,
      value: name,
    }))
  );

  const dbNames = useMemo(() => {
    return new Set(databases.map(({ name }) => name));
  }, [databases]);

  const [name, setName] = useState("");
  const [backend, setBackend] = useState("");
  const [nameConflict, setNameConflict] = useState(false);
  const [user, setUser] = useState("");
  const [password, setPassword] = useState("");

  const handleSetName = (name) => {
    setNameConflict(dbNames.has(name.trim()));
    setName(name);
  };

  const onClick = () => {
    add({
      name: name.trim(),
      backend,
      user,
      password,
    });
    setName("");
    setBackend("");
    setUser("");
    setPassword("");
    setNameConflict(false);
  };

  const disabled = !(name && backend && user && password && !nameConflict);

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
            label={__("User", "forms-bridge")}
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
          gap: "1em",
          flexWrap: "wrap",
        }}
      >
        <div>
          <label
            style={{
              display: "block",
              fontWeight: 500,
              textTransform: "uppercase",
              fontSize: "11px",
              margin: 0,
              marginBottom: "calc(4px)",
              maxWidth: "100%",
            }}
          >
            {__("Add database", "forms-bridge")}
          </label>
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
    </div>
  );
}
