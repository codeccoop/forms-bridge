// vendor
import React from "react";
import {
  TextControl,
  Button,
  __experimentalSpacer as Spacer,
} from "@wordpress/components";
import { useState, useRef, useEffect } from "@wordpress/element";

// source
import { useGeneral } from "../../providers/Settings";
import useBackendNames from "../../hooks/useBackendNames";
import BackendHeaders from "./Headers";

function NewBackend({ add }) {
  const __ = wp.i18n.__;

  const { backends } = useGeneral();
  const backendNames = useBackendNames(backends);

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
        />
        <TextControl
          style={{ minWidth: "250px" }}
          label={__("Backend base URL", "forms-bridge")}
          value={baseUrl}
          onChange={setBaseUrl}
          __nextHasNoMarginBottom
        />
        <Button
          variant="primary"
          onClick={() => onClick()}
          style={{ marginTop: "auto", height: "32px" }}
          disabled={disabled}
        >
          {__("Add", "forms-bridge")}
        </Button>
      </div>
    </div>
  );
}

let focus = false;
export default function Backend({ update, remove, ...data }) {
  if (data.name === "add") return <NewBackend add={update} />;

  const __ = wp.i18n.__;
  const [name, setName] = useState(data.name);
  const initialName = useRef(data.name);
  const nameInput = useRef();

  const { backends } = useGeneral();
  const backendNames = useBackendNames(backends);
  const [nameConflict, setNameConflict] = useState(false);
  const handleSetName = (name) => {
    setNameConflict(
      name.trim() !== initialName.current && backendNames.has(name.trim())
    );
    setName(name);
  };

  const setHeaders = (headers) => update({ ...data, headers });

  useEffect(() => {
    if (focus) {
      nameInput.current.focus();
    }
  }, []);

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
          onFocus={() => (focus = true)}
          onBlur={() => (focus = false)}
          __nextHasNoMarginBottom={true}
        />
        <TextControl
          style={{ minWidth: "250px" }}
          label={__("Backend base URL", "forms-bridge")}
          value={data.base_url}
          onChange={(base_url) => update({ ...data, base_url })}
          __nextHasNoMarginBottom={true}
        />
        <div>
          <label
            style={{
              display: "block",
              fontWeight: 500,
              textTransform: "uppercase",
              fontSize: "11px",
              marginBottom: "calc(4px)",
            }}
          >
            {__("Remove backend", "forms-bridge")}
          </label>
          <Button
            isDestructive
            variant="primary"
            onClick={() => remove(data)}
            style={{ width: "150px", justifyContent: "center", height: "32px" }}
          >
            {__("Remove", "forms-bridge")}
          </Button>
        </div>
      </div>
      <Spacer paddingY="calc(8px)" />
      <BackendHeaders headers={data.headers} setHeaders={setHeaders} />
    </div>
  );
}
