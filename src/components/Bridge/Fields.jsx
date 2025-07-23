import useBackends from "../../hooks/useBackends";
import { useForms } from "../../providers/Forms";
import { isset, prependEmptyOption } from "../../lib/utils";
import { useCredentials } from "../../hooks/useAddon";
import FieldWrapper from "../FieldWrapper";

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

export default function BridgeFields({ data, setData, schema, errors = {} }) {
  const [backends] = useBackends();
  const backendOptions = useMemo(() => {
    if (!backends.length) return [{ label: "", value: "" }];

    return backends
      .map(({ name }) => ({
        label: name,
        value: name,
      }))
      .sort((a, b) => {
        return a.label > b.label ? 1 : -1;
      });
  }, [backends]);

  const [forms] = useForms();
  const formOptions = useMemo(() => {
    if (!forms.length) return [{ label: "", value: "" }];

    return forms
      .map(({ _id, title }) => ({
        label: title,
        value: _id,
      }))
      .sort((a, b) => {
        return a.label > b.label ? 1 : -1;
      });
  }, [forms]);

  const [credentials] = useCredentials();
  const credentialOptions = useMemo(() => {
    if (!credentials.length) return [{ label: "", value: "" }];

    const options = credentials
      .filter(({ is_valid }) => is_valid)
      .map(({ name }) => ({ label: name, value: name }))
      .sort((a, b) => (a.label > b.label ? 1 : -1));

    if (!schema.required.includes("credential")) {
      return prependEmptyOption(options);
    }

    return options;
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
        }

        return field;
      });
  }, [schema, formOptions, backendOptions, credentialOptions]);

  useEffect(() => {
    const defaults = fields.reduce((defaults, field) => {
      if (field.default && !isset(data, field.name)) {
        defaults[field.name] = field.default;
      } else if (field.value && field.value !== data[field.name]) {
        defaults[field.name] = field.value;
      } else if (field.type === "options") {
        if (!field.options.length && data[field.name]) {
          defaults[field.name] = "";
        } else if (!data[field.name] || field.options.length === 1) {
          const value = field.options[0]?.value || "";
          if (value !== data[field.name]) {
            defaults[field.name] = value;
          }
        }
      } else if (field.enum && field.enum.length === 1) {
        if (data[field.name] !== field.enum[0]) {
          defaults[field.name] = field.enum[0];
        }
      }

      if (!forms.length && data.form_id) {
        defaults.form_id = "";
      }

      if (!backends.length && data.backend) {
        defaults.backend = "";
      }

      if (!credentials.length && data.credential) {
        defaults.credential = "";
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
            <StringField
              error={errors[field.name]}
              label={field.label}
              value={data[field.name] || ""}
              setValue={(value) => setData({ ...data, [field.name]: value })}
            />
          );
        case "options":
          return (
            <OptionsField
              error={errors[field.name]}
              label={field.label}
              value={data[field.name] || ""}
              setValue={(value) => setData({ ...data, [field.name]: value })}
              options={field.options}
            />
          );
      }
    });
}

export function StringField({ label, value, setValue, error, disabled }) {
  return (
    <FieldWrapper>
      <TextControl
        disabled={disabled}
        label={label}
        value={value}
        onChange={setValue}
        help={error}
        __nextHasNoMarginBottom
        __next40pxDefaultSize
      />
    </FieldWrapper>
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
    <FieldWrapper>
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
    </FieldWrapper>
  );
}
