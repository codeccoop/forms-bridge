import { useGeneral } from "../../../providers/Settings";
import { useTemplateConfig } from "../../../providers/Templates";
import useBackendNames from "../../../hooks/useBackendNames";
import TemplateStep from "./Step";
import Field from "../Field";
import { sortByNamesOrder, prependEmptyOption } from "../../../lib/utils";

const { SelectControl } = wp.components;
const { useMemo, useState, useEffect, useRef } = wp.element;
const { __ } = wp.i18n;

const FIELDS_ORDER = ["name", "base_url", "headers"];

function validateBackend(backend, schema, fields) {
  if (!backend?.name) return false;

  const isValid = fields.reduce((isValid, { name, ref, required }) => {
    if (!isValid || !required) return isValid;

    let value;
    if (ref === "#backend/headers[]") {
      value = backend.headers.find((header) => header.name === name)?.value;
    } else {
      value = backend[name];
    }

    return value !== undefined && value !== null;
  }, true);

  if (!isValid) return isValid;

  if (schema.base_url && backend.base_url !== schema.base_url) {
    return false;
  }

  return schema.headers.reduce((isValid, { name, value }) => {
    if (!isValid) return isValid;

    const header = backend.headers.find((header) => header.name === name);
    if (!header) return false;

    return header.value === value;
  }, isValid);
}

export default function BackendStep({ fields, data, setData, wired }) {
  const [{ backends }] = useGeneral();
  const names = useBackendNames();
  const { backend: schema } = useTemplateConfig();

  const validBackends = useMemo(
    () =>
      backends.filter((backend) => validateBackend(backend, schema, fields)),
    [backends]
  );

  const backendOptions = useMemo(() => {
    return prependEmptyOption(
      validBackends.map(({ name }) => ({ label: name, value: name }))
    );
  }, [validBackends]);

  const previousReuse = useRef(
    backendOptions.find(({ value }) => value === data.name)?.value || ""
  );
  const [reuse, setReuse] = useState(() => {
    return backendOptions.find(({ value }) => value === data.name)?.value || "";
  });

  const [name, setName] = useState(data.name || "");
  const nameConflict = useMemo(
    () => data.name !== name.trim() && names.has(name.trim()),
    [names, name]
  );

  useEffect(() => {
    if (reuse !== previousReuse.current) {
      setName("");
      setData();
    } else if (!reuse && !nameConflict && data.name !== name) {
      setData({ name });
    }

    previousReuse.current = reuse;
  }, [data.name, reuse, name, nameConflict]);

  const backend = useMemo(
    () => validBackends.find((backend) => backend.name === reuse),
    [validBackends, reuse]
  );

  useEffect(() => {
    if (!backend || reuse !== previousReuse.current) return;

    const headers = backend.headers.reduce(
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
  }, [backend]);

  const sortedFields = useMemo(
    () => sortByNamesOrder(fields, FIELDS_ORDER),
    [fields]
  );

  const filteredFields = useMemo(() => {
    if (backend) return [];
    return sortedFields.slice(1);
  }, [backend, sortedFields]);

  const nameField = useMemo(() => sortedFields[0], [sortedFields]);

  const statusIcon = useMemo(() => {
    if (wired === true) {
      return "👌";
    } else if (wired === false) {
      return "👎";
    } else if (validateBackend(backend, schema, fields)) {
      return "⏳";
    }

    return null;
  }, [wired, backend, schema, fields]);

  return (
    <TemplateStep
      name={__("Backend", "forms-bridge")}
      description={__(
        "Configure the backend to bridge your form to",
        "forms-bridge"
      )}
    >
      <p>
        <strong>
          Connection status: <span>{statusIcon}</span>
        </strong>
      </p>
      {backendOptions.length > 0 && (
        <SelectControl
          label={__("Reuse an existing backend", "forms-bridge")}
          value={reuse}
          options={backendOptions}
          onChange={setReuse}
          __nextHasNoMarginBottom
          __next40pxDefaultSize
        />
      )}
      {(!reuse && (
        <Field
          data={{
            ...nameField,
            value: name,
            onChange: setName,
          }}
          error={
            nameConflict
              ? __("This name is already in use", "forms-bridge")
              : false
          }
        />
      )) ||
        null}
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
