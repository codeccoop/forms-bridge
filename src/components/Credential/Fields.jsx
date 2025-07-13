import { StringField, OptionsField } from "../Bridge/Fields";
const { useEffect, useMemo } = wp.element;

export const INTERNALS = [
  "enabled",
  "is_valid",
  "access_token",
  "refresh_token",
  "expires_at",
];

export default function CredentialFields({
  data,
  setData,
  schema,
  optionals = false,
  errors,
}) {
  const fields = useMemo(() => {
    if (!schema) return [];

    return Object.keys(schema.properties)
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
        } else if (field.$ref) {
          const options = setting[field.$ref] || [];
          return { ...field, type: "options", options };
        }

        return field;
      });
  }, [schema]);

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
    .sort((a, b) => (a.name === "name" ? -1 : 0))
    .map((field) => {
      switch (field.type) {
        case "string":
          return (
            <div style={{ flex: 1, maxWidth: "250px" }}>
              <StringField
                style={{ maxWidth: "300px" }}
                label={field.label}
                value={data[field.name] || ""}
                setValue={(value) => setData({ ...data, [field.name]: value })}
                error={errors[field.name]}
              />
            </div>
          );
        case "options":
          return (
            <div style={{ flex: 1, maxWidth: "250px" }}>
              <OptionsField
                label={field.label}
                value={data[field.name] || ""}
                setValue={(value) => setData({ ...data, [field.name]: value })}
                options={field.options}
                optional={optionals}
                error={errors[field.name]}
              />
            </div>
          );
      }
    });
}
