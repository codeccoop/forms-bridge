import { isset } from "../../lib/utils";
import { StringField, OptionsField } from "../Bridge/Fields";
const { useEffect, useMemo } = wp.element;

export const INTERNALS = [
  "is_valid",
  // "access_token",
  "refresh_token",
  // "expires_at",
  "refresh_token_expires_at",
];

export default function CredentialFields({
  data,
  setData,
  schema,
  disabled = false,
  errors,
}) {
  const fields = useMemo(() => {
    if (!schema) return [];

    return Object.keys(schema.properties)
      .filter((name) => schema.properties[name].public !== false)
      .map((name) => ({
        ...schema.properties[name],
        label: schema.properties[name].name || name,
        name,
      }))
      .map((field) => {
        if (field.enum) {
          return {
            ...field,
            type: "options",
            options: field.enum.map((value) => ({ label: value, value })),
          };
        }

        return field;
      });
  }, [schema]);

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
          data[field.name] = field.enum[0];
        }
      }

      return defaults;
    }, {});

    if (Object.keys(defaults).length) {
      setData({ ...data, ...defaults });
    }
  }, [data, fields]);

  const realmRequired = ["Digest", "RPC", "Bearer"].includes(data.schema);

  return fields
    .filter((field) => !field.value)
    .filter((field) => (!realmRequired ? field.name !== "realm" : true))
    .sort((a, b) => (a.name === "name" ? -1 : 0))
    .map((field) => {
      switch (field.type) {
        case "string":
          return (
            <StringField
              label={field.label}
              value={data[field.name] || ""}
              setValue={(value) => setData({ ...data, [field.name]: value })}
              error={errors[field.name]}
              disabled={disabled}
            />
          );
        case "options":
          return (
            <OptionsField
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
