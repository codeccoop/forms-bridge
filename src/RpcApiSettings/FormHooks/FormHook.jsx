// vendor
import React from "react";
import {
  TextControl,
  SelectControl,
  Button,
  __experimentalSpacer as Spacer,
} from "@wordpress/components";
import { useState, useRef, useEffect } from "@wordpress/element";

// source
import { useForms } from "../../providers/Forms";
import { useGeneral } from "../../providers/Settings";
import useHookNames from "../../hooks/useHookNames";
import FormPipes from "../../FormPipes";

function NewFormHook({ add }) {
  const __ = wp.i18n.__;
  const [{ backends }] = useGeneral();
  const backendOptions = backends.map(({ name }) => ({
    label: name,
    value: name,
  }));
  const forms = useForms();
  const formOptions = forms.map(({ id, title }) => ({
    label: title,
    value: id,
  }));

  const hookNames = useHookNames();

  const [name, setName] = useState("");
  const [backend, setBackend] = useState(backendOptions[0]?.value || "");
  const [model, setModel] = useState("");
  const [formId, setFormId] = useState(formOptions[0]?.value || "");
  const [nameConflict, setNameConflict] = useState(false);

  const handleSetName = (name) => {
    setNameConflict(hookNames.has(name));
    setName(name.trim());
  };

  const onClick = () => add({ name, backend, model, form_id: formId });

  const disabled = !(name && backend && model && formId && !nameConflict);

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
          />
        </div>
        <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
          <SelectControl
            label={__("Backend", "forms-bridge")}
            value={backend}
            onChange={setBackend}
            options={backendOptions}
            __nextHasNoMarginBottom
          />
        </div>
        <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
          <TextControl
            label={__("Model", "forms-bridge")}
            value={model}
            onChange={setModel}
            __nextHasNoMarginBottom
          />
        </div>
        <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
          <SelectControl
            label={__("Form", "forms-bridge")}
            value={formId}
            onChange={setFormId}
            options={formOptions}
            __nextHasNoMarginBottom
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
            {__("Add form", "forms-bridge")}
          </label>
          <Button
            variant="primary"
            onClick={() => onClick()}
            style={{ width: "130px", justifyContent: "center", height: "32px" }}
            disabled={disabled}
          >
            {__("Add", "forms-bridge")}
          </Button>
        </div>
      </div>
    </div>
  );
}

let focus;
export default function FormHook({ update, remove, ...data }) {
  if (data.name === "add") return <NewFormHook add={update} />;

  const __ = wp.i18n.__;
  const [{ backends }] = useGeneral();
  const backendOptions = backends.map(({ name }) => ({
    label: name,
    value: name,
  }));
  const forms = useForms();
  const formOptions = forms.map(({ id, title }) => ({
    label: title,
    value: id,
  }));

  const [name, setName] = useState(data.name);
  const initialName = useRef(data.name);
  const nameInput = useRef();

  const hookNames = useHookNames();
  const [nameConflict, setNameConflict] = useState(false);
  const handleSetName = (name) => {
    setNameConflict(name !== initialName.current && hookNames.has(name));
    setName(name.trim());
  };

  useEffect(() => {
    if (focus) {
      nameInput.current.focus();
    }
  }, []);

  const timeout = useRef();
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
          flexWrap: "wrap",
        }}
      >
        <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
          <TextControl
            ref={nameInput}
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
          />
        </div>
        <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
          <SelectControl
            label={__("Backend", "forms-bridge")}
            value={data.backend}
            onChange={(backend) => update({ ...data, backend })}
            options={backendOptions}
            __nextHasNoMarginBottom
          />
        </div>
        <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
          <TextControl
            label={__("Model", "forms-bridge")}
            value={data.model}
            onChange={(model) => update({ ...data, model })}
            __nextHasNoMarginBottom
          />
        </div>
        <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
          <SelectControl
            label={__("Form", "forms-bridge")}
            value={data.form_id}
            onChange={(form_id) => update({ ...data, form_id })}
            options={formOptions}
            __nextHasNoMarginBottom
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
              marginBottom: "calc(4px)",
            }}
          >
            {__("Edit pipes", "forms-bridge")}
          </label>
          <FormPipes
            formId={data.form_id}
            pipes={data.pipes || []}
            setPipes={(pipes) => update({ ...data, pipes })}
          />
        </div>
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
            {__("Remove form", "forms-bridge")}
          </label>
          <Button
            isDestructive
            variant="primary"
            onClick={() => remove(data)}
            style={{ width: "130px", justifyContent: "center", height: "32px" }}
          >
            {__("Remove", "forms-bridge")}
          </Button>
        </div>
      </div>
    </div>
  );
}
