import { useBackends } from "../../hooks/useHttp";
import { useForms } from "../../providers/Forms";
import { isset, prependEmptyOption } from "../../lib/utils";
import FieldWrapper from "../FieldWrapper";
import { useApiEndpoints } from "../../providers/ApiSchema";

const { BaseControl, SelectControl } = wp.components;
const { useEffect, useMemo } = wp.element;

export const INTERNALS = [
  "enabled",
  "is_valid",
  "workflow",
  "custom_fields",
  "mutations",
];

const ORDER = ["name", "form_id", "backend", "endpoint", "method"];

export default function BridgeFields({ data, setData, schema, errors = {} }) {
  const endpoints = useApiEndpoints();

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
    const options = [{ label: "", value: "" }];
    if (!forms.length) return options;

    return options.concat(
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

  const fields = useMemo(() => {
    if (!schema) return [];

    return Object.keys(schema.properties)
      .filter((name) => !INTERNALS.includes(name))
      .map((name) => ({
        ...schema.properties[name],
        label: schema.properties[name].title || name,
        name,
        value: schema.properties[name].const,
      }))
      .map((field) => {
        if (field.name === "form_id") {
          return {
            ...field,
            type: "select",
            options: formOptions,
          };
        } else if (field.name === "backend") {
          return {
            ...field,
            type: "select",
            options: backendOptions,
          };
        } else if (field.enum) {
          return {
            ...field,
            type: "select",
            options: field.enum.map((value) => ({ label: value, value })),
          };
        }

        return field;
      });
  }, [schema, formOptions, backendOptions]);

  useEffect(() => {
    const defaults = fields.reduce((defaults, field) => {
      if (field.default && !isset(data, field.name)) {
        defaults[field.name] = field.default;
      } else if (field.value && field.value !== data[field.name]) {
        defaults[field.name] = field.value;
      } else if (field.type === "select") {
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
              key={field.name}
              error={errors[field.name]}
              label={field.label}
              value={data[field.name] || ""}
              setValue={(value) => setData({ ...data, [field.name]: value })}
              datalist={field.name === "endpoint" ? endpoints : []}
            />
          );
        case "select":
          return (
            <SelectField
              key={field.name}
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

export function StringField({
  label,
  value,
  setValue,
  error,
  disabled,
  datalist = [],
}) {
  return (
    <FieldWrapper>
      <BaseControl label={label} help={error}>
        <input
          name={label}
          type="text"
          list={"datalist-" + label}
          value={value}
          onChange={(ev) => setValue(ev.target.value)}
          disabled={disabled}
          style={{
            height: "40px",
            paddingRight: "12px",
            paddingLeft: "12px",
            borderColor: "var(--wp-components-color-gray-600,#949494)",
            fontSize: "13px",
            width: "100%",
            border: "1px solid #949494",
            borderRadius: "2px",
          }}
        />
        <datalist id={"datalist-" + label}>
          {datalist.map((endpoint) => (
            <option key={endpoint} value={endpoint}></option>
          ))}
        </datalist>
      </BaseControl>
    </FieldWrapper>
  );
}

export function SelectField({
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
