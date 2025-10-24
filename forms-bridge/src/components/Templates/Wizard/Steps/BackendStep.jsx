import { useTemplateConfig } from "../../../../providers/Templates";
import { useBackends } from "../../../../hooks/useHttp";
import useBackendNames from "../../../../hooks/useBackendNames";
import TemplateStep from "./Step";
import Field from "../../Field";
import { sortByNamesOrder, prependEmptyOption } from "../../../../lib/utils";
import { validateBackend, mockBackend } from "../lib";
import diff from "../../../../lib/diff";

const { SelectControl } = wp.components;
const { useMemo, useState, useEffect, useRef } = wp.element;
const { __ } = wp.i18n;

const FIELDS_ORDER = ["name", "base_url", "headers"];

export default function BackendStep({
  fields,
  data,
  setData,
  wired,
  fetched,
  credential,
}) {
  const [backends] = useBackends();
  const names = useBackendNames();
  const [{ backend: template }] = useTemplateConfig();

  const sortedFields = useMemo(
    () => sortByNamesOrder(fields, FIELDS_ORDER),
    [fields]
  );

  const [state, setState] = useState({ ...data });

  const defaults = useMemo(() => {
    const defaults = fields.reduce((defaults, field) => {
      let val = field.value || field.default || "";
      if (!val && field.type === "select" && field.required) {
        val = field.options[0].value;
      }

      defaults[field.name] = val;
      return defaults;
    }, {});

    return {
      base_url: template.base_url,
      name: template.name,
      credential: template.credential,
      ...defaults,
    };
  }, [fields, template]);

  const validBackends = useMemo(() => {
    return backends
      .filter((backend) => validateBackend(backend, template, fields))
      .filter((backend) => {
        if (!credential) return backend;
        return backend.credential === credential;
      });
  }, [backends, template, fields]);

  const backendOptions = useMemo(() => {
    return prependEmptyOption(
      validBackends.map(({ name }) => ({ label: name, value: name }))
    ).sort((a, b) => (a.label > b.label ? 1 : -1));
  }, [validBackends]);

  const [reuse, setReuse] = useState(() => {
    return backendOptions.find(({ value }) => value === data.name)?.value || "";
  });

  const nameConflict = useMemo(() => {
    return state.name && names.has(state.name.trim());
  }, [names, state.name]);

  const mockedBackend = useMemo(() => {
    if (nameConflict) return;

    const backend = mockBackend(state, template, fields);
    if (validateBackend(backend, template, fields)) {
      return backend;
    }
  }, [state, nameConflict, template, fields]);

  const backend = useMemo(() => {
    let backend = validBackends.find((b) => b.name === reuse);
    if (backend) return backend;
    return mockedBackend;
  }, [validBackends, reuse, mockedBackend]);

  useEffect(() => {
    if (!backend) {
      setData(null);
      return;
    }

    if (reuse) {
      setState({ ...defaults });
    }

    const data = { name: backend.name, base_url: backend.base_url };

    backend.headers
      .filter(({ name }) => {
        return fields.find((f) => f.name === name);
      })
      .forEach(({ name, value }) => (data[name] = value));

    if (backend.authentication?.type) {
      data.client_id = backend.authentication.client_id;
      data.client_secret = backend.authentication.client_secret;
    }

    setData(data);
  }, [reuse, backend, fields]);

  const fromTemplate = useRef(template);
  useEffect(() => {
    if (diff(template, fromTemplate.current)) {
      setReuse("");
    }

    return () => {
      fromTemplate.current = template;
    };
  }, [template]);

  const statusIcon = useMemo(() => {
    if (wired === true && fetched === true) {
      return "👌";
    } else if (wired === false) {
      return "👎";
    } else if (backend) {
      return "⏳";
    }

    return null;
  }, [wired, fetched, backend]);

  return (
    <TemplateStep
      name={__("Backend", "forms-bridge")}
      description={__(
        "Configure the backend to bridge your form to",
        "forms-bridge"
      )}
    >
      <p
        style={{
          height: "30px",
          marginTop: 0,
          display: "flex",
          alignItems: "center",
        }}
      >
        <strong>{__("Connection status", "forms-bridge")}:</strong>
        <i style={{ fontSize: "1.5em", marginLeft: "0.25em" }}>{statusIcon}</i>
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
      {!reuse &&
        sortedFields.map((field) => (
          <Field
            key={field.name}
            data={{
              ...field,
              value: state[field.name] || "",
              onChange: (value) => setState({ ...state, [field.name]: value }),
            }}
            error={
              field.name === "name" &&
              nameConflict &&
              __("This name is already in use", "forms-bridge")
            }
          />
        ))}
    </TemplateStep>
  );
}
