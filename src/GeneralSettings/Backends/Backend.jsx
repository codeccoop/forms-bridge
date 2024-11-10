// vendor
import React from "react";
import { __ } from "@wordpress/i18n";
import {
  TextControl,
  Button,
  __experimentalSpacer as Spacer,
} from "@wordpress/components";
import { useState, useRef, useEffect } from "@wordpress/element";

// source
import BackendHeaders from "./Headers";
import useBackendNames from "../../hooks/useBackendNames";

function NewBackend({ add }) {
  const backendNames = useBackendNames();

  const [name, setName] = useState("");
  const [baseUrl, setBaseUrl] = useState("https://");
  const [nameConflict, setNameConflict] = useState(false);

  const handleSetName = (name) => {
    setNameConflict(backendNames.has(name));
    setName(name.trim());
  };

  const onClick = () => add({ name, base_url: baseUrl, headers: [] });

  const disabled = !(name && baseUrl && !disabled);

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
          label={__("Backend name", "wpct-erp-forms")}
          help={
            nameConflict
              ? __("This name is already in use", "wpct-erp-forms")
              : ""
          }
          value={name}
          onChange={setName}
          __nextHasNoMarginBottom
        />
        <TextControl
          style={{ minWidth: "250px" }}
          label={__("Backend base URL", "wpct-erp-forms")}
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
          {__("Add", "wpct-erp-forms")}
        </Button>
      </div>
    </div>
  );
}

let focus = false;
export default function Backend({ update, remove, ...data }) {
  if (data.name === "add") return <NewBackend add={update} />;

  const [name, setName] = useState(data.name);
  const initialName = useRef(data.name);
  const nameInput = useRef();

  const backendNames = useBackendNames();
  const [nameConflict, setNameConflict] = useState(false);
  const handleSetName = (name) => {
    console.log(backendNames, name);
    setNameConflict(name !== initialName.current && backendNames.has(name));
    setName(name.trim());
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
    timeout.current = setTimeout(() => update({ ...data, name }), 500);
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
          label={__("Backend name", "wpct-erp-forms")}
          help={
            nameConflict
              ? __("This name is already in use", "wpct-erp-forms")
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
          label={__("Backend base URL", "wpct-erp-forms")}
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
            {__("Remove backend", "wpct-erp-forms")}
          </label>
          <Button
            isDestructive
            variant="primary"
            onClick={() => remove(data)}
            style={{ width: "130px", justifyContent: "center", height: "32px" }}
          >
            {__("Remove", "wpct-erp-forms")}
          </Button>
        </div>
      </div>
      <Spacer paddingY="calc(8px)" />
      <BackendHeaders headers={data.headers} setHeaders={setHeaders} />
    </div>
  );
}
