import { useGeneral } from "../../../providers/Settings";
import useBackendNames from "../../../hooks/useBackendNames";
import TemplateStep from "./Step";
import Field from "../Field";

const { SelectControl } = wp.components;
const { useMemo, useState, useEffect } = wp.element;
const { __ } = wp.i18n;

const fieldsOrder = ["name", "base_url", "headers"];

export default function BackendStep({ fields, data, setData }) {
  const [{ backends }] = useGeneral();
  const backendNames = useBackendNames();
  const [backendName, setBackendName] = useState(
    backendNames.has(data.name) ? data.name : ""
  );
  const [newName, setNewName] = useState(data.name || "");

  const backendOptions = [{ label: "", value: "" }].concat(
    Array.from(backendNames).map((name) => ({ label: name, value: name }))
  );

  const backend = useMemo(
    () => backends.find(({ name }) => name === backendName),
    [backends, backendName]
  );

  useEffect(() => {
    if (backend) {
      const headers = backend.headers
        .filter(({ name }) => fields.find((field) => field.name === name))
        .reduce(
          (headers, header) => ({
            ...headers,
            [header.name]: header.value,
          }),
          {}
        );

      setData({
        ...headers,
        name: backend.name,
        base_url: backend.base_url,
      });
    } else {
      setData(Object.fromEntries(fields.map(({ name }) => [name, null])));
    }
  }, [backend]);

  const sortedFields = useMemo(
    () =>
      fields.sort((a, b) => {
        if (!fieldsOrder.includes(a.name)) {
          return 1;
        } else if (!fieldsOrder.includes(b.name)) {
          return -1;
        } else {
          fieldsOrder.indexOf(a.name) - fieldsOrder.indexOf(b.name);
        }
      }),
    [fields]
  );

  const filteredFields = useMemo(
    () =>
      backendName ? [] : sortedFields.filter(({ name }) => name !== "name"),
    [backendName, sortedFields]
  );

  const nameField = useMemo(
    () => sortedFields.find(({ name }) => name === "name"),
    [sortedFields]
  );

  const nameConflict = useMemo(
    () => (newName && backendNames.has(newName.trim())) || false,
    [backendNames, newName]
  );

  useEffect(() => {
    if (!data.name) return;

    if (!backendName && backendNames.has(data.name)) {
      setBackendName(data.name);
    } else if (data.name !== newName) {
      setNewName(data.name);
    }
  }, [data.name]);

  useEffect(() => {
    if (!nameConflict && newName) {
      setData({ name: newName });
    }
  }, [newName]);

  useEffect(() => {
    if (backendName) {
      setNewName("");
    }
  }, [backendName]);

  return (
    <TemplateStep
      name={__("Backend", "forms-bridge")}
      description={__(
        "Configure the backend to bridge your form to",
        "forms-bridge"
      )}
    >
      <SelectControl
        label={__("Reuse backend", "forms-bridge")}
        value={backendName}
        options={backendOptions}
        onChange={(value) => setBackendName(value)}
        __nextHasNoMarginBottom
      />
      {!backendName && (
        <Field
          error={
            nameConflict
              ? __("This name is already in use", "forms-bridge")
              : false
          }
          data={{
            ...nameField,
            value: newName || "",
            onChange: setNewName,
          }}
        />
      )}
      {filteredFields.map((field) => (
        <Field
          data={{
            ...field,
            value: data[field.name] || "",
            onChange: (value) => setData({ [field.name]: value }),
          }}
        />
      ))}
    </TemplateStep>
  );
}
