// vendor
import React from "react";
import { __ } from "@wordpress/i18n";
import { TextControl, SelectControl, Button } from "@wordpress/components";
import { useState, useRef, useEffect } from "@wordpress/element";

// source
import { useForms } from "../../providers/Forms";
import { useGeneral } from "../../providers/Settings";

function NewForm({ add }) {
  const [{ backends }] = useGeneral();
  const backendOptions = backends.map(({ name, base_url }) => ({
    label: name,
    value: base_url,
  }));
  const forms = useForms();
  const formOptions = forms.map(({ id, title }) => ({
    label: title,
    value: id,
  }));

  const [name, setName] = useState("");
  const [backend, setBackend] = useState("");
  const [model, setModel] = useState("");
  const [formId, setFormId] = useState("");

  const onClick = () => add({ name, backend, model, form_id: formId });

  const disabled = !(name, backend, model, formId);

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
          label={__("Bound ID", "wpct-erp-forms")}
          value={name}
          onChange={setName}
          __nextHasNoMarginBottom
        />
        <SelectControl
          label={__("Backend", "wpct-erp-forms")}
          value={backend}
          onChange={setBackend}
          options={backendOptions}
          __nextHasNoMarginBottom
        />
        <TextControl
          label={__("Model", "wpct-erp-forms")}
          value={model}
          onChange={setModel}
          __nextHasNoMarginBottom
        />
        <SelectControl
          label={__("Form", "wpct-erp-forms")}
          value={formId}
          onChange={setFormId}
          options={formOptions}
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

let focus;
export default function Form({ update, remove, ...data }) {
  if (data.name === "add") return <NewForm add={update} />;

  const [{ backends }] = useGeneral();
  const backendOptions = backends.map(({ name, base_url }) => ({
    label: name,
    value: base_url,
  }));
  const forms = useForms();
  const formOptions = forms.map(({ id, title }) => ({
    label: title,
    value: id,
  }));

  const [name, setName] = useState(data.name);
  const nameInput = useRef();

  useEffect(() => {
    if (focus) {
      nameInput.current.focus();
    }
  }, []);

  const timeout = useRef(false);
  useEffect(() => {
    if (timeout.current === false) {
      timeout.current = 0;
      return;
    }

    clearTimeout(timeout.current);
    timeout.current = setTimeout(() => update({ ...data, name }), 500);
  }, [name]);

  useEffect(() => {
    timeout.current = false;
    setName(data.name);
  }, [data.name]);

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
          label={__("Bound ID", "wpct-erp-forms")}
          value={name}
          onChange={setName}
          onFocus={() => (focus = true)}
          onBlur={() => (focus = false)}
          __nextHasNoMarginBottom={true}
        />
        <SelectControl
          label={__("Backend", "wpct-erp-forms")}
          value={data.backend}
          onChange={(backend) => update({ ...data, backend })}
          options={backendOptions}
          __nextHasNoMarginBottom
        />
        <TextControl
          label={__("Model", "wpct-erp-forms")}
          value={data.model}
          onChange={(model) => update({ ...data, model })}
          __nextHasNoMarginBottom={true}
        />
        <SelectControl
          label={__("Form", "wpct-erp-forms")}
          value={data.form_id}
          onChange={(form_id) => update({ ...data, form_id })}
          options={formOptions}
          __nextHasNoMarginBottom
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
            {__("Remove form", "wpct-erp-forms")}
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
    </div>
  );
}
