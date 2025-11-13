import { isset } from "../../lib/utils";
import { StringField, SelectField } from "../Bridge/Fields";
const { useEffect, useMemo } = wp.element;

export const INTERNALS = [
  // "access_token",
  "refresh_token",
  // "expires_at",
  "refresh_token_expires_at",
];

export default function CredentialFields({
  data,
  setData,
  schema,
  schemas,
  disabled = false,
  errors,
}) {
  const schemaOptions = useMemo(() => {
    return schemas.oneOf
      .reduce((options, schema) => {
        if (schema.properties.schema.const) {
          return options.concat(schema.properties.schema.const);
        }

        return options.concat(schema.properties.schema.enum || []);
      }, [])
      .map((opt) => ({ value: opt, label: opt }));
  }, [schemas]);

  const fields = useMemo(() => {
    if (!schema) return [];

    return Object.keys(schema.properties)
      .filter((name) => schema.properties[name].public !== false)
      .map((name) => ({
        ...schema.properties[name],
        label: schema.properties[name].title || name,
        name,
        value: schema.properties[name].const,
      }))
      .map((field) => {
        if (field.name === "schema") {
          return {
            ...field,
            type: "select",
            options: schemaOptions,
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
  }, [schema]);

  useEffect(() => {
    const defaults = fields.reduce((defaults, field) => {
      if (
        (field.name === "realm" ||
          field.name === "scope" ||
          field.name === "database") &&
        !isset(data, field.name)
      ) {
        defaults[field.name] = data.realm || data.scope || data.database || "";

        field.name !== "realm" && delete data.realm;
        field.name !== "scope" && delete data.scope;
        field.name !== "database" && delete data.database;
      }

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
          data[field.name] = field.enum[0];
        }
      }

      return defaults;
    }, {});

    if (Object.keys(defaults).length) {
      setData({ ...data, ...defaults });
    }
  }, [data, fields]);

  return fields
    .filter((field) => !field.value)
    .sort((a) => (a.name === "name" ? -1 : 0))
    .map((field) => {
      switch (field.type) {
        case "string":
          return (
            <StringField
              key={field.name}
              label={field.label}
              value={data[field.name] || ""}
              setValue={(value) => setData({ ...data, [field.name]: value })}
              error={errors[field.name]}
              disabled={disabled}
            />
          );
        case "select":
          return (
            <SelectField
              key={field.name}
              label={field.label}
              value={data[field.name] || ""}
              setValue={(value) => setData({ ...data, [field.name]: value })}
              options={field.options}
              optional={!schema.required.includes(field.name)}
              error={errors[field.name]}
              disabled={disabled}
            />
          );
      }
    });
}
