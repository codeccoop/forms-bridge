import useBackends from "../../hooks/useBackends";
import { useForms } from "../../providers/Forms";
import { prependEmptyOption } from "../../lib/utils";
import { useCredentials } from "../../hooks/useAddon";

const { TextControl, SelectControl } = wp.components;
const { useEffect, useMemo } = wp.element;

export const INTERNALS = [
  "enabled",
  "is_valid",
  "workflow",
  "custom_fields",
  "mutations",
];

const ORDER = [
  "name",
  "form_id",
  "backend",
  "credential",
  "endpoint",
  "method",
];

export default function BridgeFields({
  data,
  setData,
  schema,
  optionals = false,
  setting = {},
  errors = {},
}) {
  const [backends] = useBackends();
  const backendOptions = useMemo(() => {
    return prependEmptyOption(
      backends
        .map(({ name }) => ({
          label: name,
          value: name,
        }))
        .sort((a, b) => {
          return a.label > b.label ? 1 : -1;
        })
    );
  }, [backends]);

  const [forms] = useForms();
  const formOptions = useMemo(() => {
    return prependEmptyOption(
      forms
        .map(({ _id, title }) => ({
          label: title,
          value: _id,
        }))
        .sort((a, b) => {
          return a.label > b.label ? 1 : -1;
        })
    );
  }, [forms]);

  const [credentials] = useCredentials();
  const credentialOptions = useMemo(() => {
    return prependEmptyOption(
      credentials
        .filter(({ is_valid, enabled }) => is_valid && enabled)
        .map(({ name }) => ({ label: name, value: name }))
        .sort((a, b) => (a.label > b.label ? 1 : -1))
    );
  }, [credentials]);

  const fields = useMemo(() => {
    if (!schema) return [];

    return Object.keys(schema.properties)
      .filter((name) => !INTERNALS.includes(name))
      .map((name) => ({
        ...schema.properties[name],
        label: schema.properties[name].name || name,
        name,
      }))
      .map((field) => {
        if (field.name === "form_id") {
          return {
            ...field,
            type: "options",
            options: formOptions,
          };
        } else if (field.name === "backend") {
          return {
            ...field,
            type: "options",
            options: backendOptions,
          };
        } else if (field.name === "credential") {
          return {
            ...field,
            type: "options",
            options: credentialOptions,
          };
        } else if (field.enum) {
          return {
            ...field,
            type: "options",
            options: field.enum.map((value) => ({ label: value, value })),
          };
        } else if (field.$ref) {
          const options = setting[field.$ref] || [];
          return { ...field, type: "options", options };
        }

        return field;
      });
  }, [schema, formOptions, backendOptions, credentialOptions]);

  useEffect(() => {
    const defaults = fields.reduce((defaults, field) => {
      if (
        field.default &&
        !Object.prototype.hasOwnProperty.call(data, field.name)
      ) {
        defaults[field.name] = field.default;
      } else if (field.value && field.value !== data[field.name]) {
        defaults[field.name] = field.value;
      }

      return defaults;
    }, {});

    if (Object.keys(defaults).length) {
      setData({ ...data, ...defaults });
    }
  }, [data, fields]);

  return fields
    .filter((field) => !field.value)
    .sort((a, b) =>
      ORDER.includes(a.name) && ORDER.includes(b.name)
        ? ORDER.indexOf(a.name) - ORDER.indexOf(b.name)
        : 0
    )
    .map((field) => {
      switch (field.type) {
        case "string":
          return (
            <div style={{ flex: 1, maxWidth: "250px" }}>
              <StringField
                error={errors[field.name]}
                label={field.label}
                value={data[field.name] || ""}
                setValue={(value) => setData({ ...data, [field.name]: value })}
              />
            </div>
          );
        case "options":
          return (
            <div style={{ flex: 1, maxWidth: "250px" }}>
              <OptionsField
                error={errors[field.name]}
                label={field.label}
                value={data[field.name] || ""}
                setValue={(value) => setData({ ...data, [field.name]: value })}
                options={field.options}
                optional={optionals}
              />
            </div>
          );
      }
    });
}

export function StringField({ label, value, setValue, error, disabled }) {
  return (
    <div style={{ flex: 1 }}>
      <TextControl
        disabled={disabled}
        label={label}
        value={value}
        onChange={setValue}
        help={error}
        __nextHasNoMarginBottom
        __next40pxDefaultSize
      />
    </div>
  );
}

export function OptionsField({
  label,
  options,
  value,
  setValue,
  optional,
  error,
  disabled,
}) {
  if (optional) {
    options = prependEmptyOption(options);
  }

  return (
    <div style={{ flex: 1 }}>
      <SelectControl
        disabled={disabled}
        label={label}
        value={value}
        onChange={setValue}
        options={options}
        help={error}
        __nextHasNoMarginBottom
        __next40pxDefaultSize
      />
    </div>
  );
}
