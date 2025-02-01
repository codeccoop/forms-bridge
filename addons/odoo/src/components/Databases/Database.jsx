// vendor
import React from "react";
import {
  TextControl,
  SelectControl,
  Button,
  __experimentalSpacer as Spacer,
} from "@wordpress/components";
import { useState, useRef, useEffect, useMemo } from "@wordpress/element";

// source
import { useGeneral } from "../../../../../src/providers/Settings";
import NewDatabase from "./NewDatabase";

export default function Database({ data, update, remove, databases }) {
  if (data.name === "add")
    return <NewDatabase add={update} databases={databases} />;

  const __ = wp.i18n.__;

  const [{ backends }] = useGeneral();
  const backendOptions = [{ label: "", value: "" }].concat(
    backends.map(({ name }) => ({
      label: name,
      value: name,
    }))
  );

  const [name, setName] = useState(data.name);
  const initialName = useRef(data.name);

  const dbNames = useMemo(() => {
    return new Set(databases.map(({ name }) => name));
  }, [databases]);
  const [nameConflict, setNameConflict] = useState(false);
  const handleSetName = (name) => {
    setNameConflict(name !== initialName.current && dbNames.has(name.trim()));
    setName(name);
  };

  const timeout = useRef();
  useEffect(() => {
    clearTimeout(timeout.current);
    if (!name || nameConflict) return;
    timeout.current = setTimeout(() => {
      if (dbNames.has(name.trim())) return;
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
            onFocus={() => (focus = true)}
            onBlur={() => (focus = false)}
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
            label={__("User", "forms-bridge")}
            value={data.user}
            onChange={(user) => update({ ...data, user })}
            __nextHasNoMarginBottom
            __next40pxDefaultSize
          />
        </div>
        <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
          <TextControl
            type="password"
            label={__("Password", "forms-bridge")}
            value={data.password}
            onChange={(password) => update({ ...data, password })}
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
            {__("Remove database", "forms-bridge")}
          </label>
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
    </div>
  );
}
