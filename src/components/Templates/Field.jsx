const { __ } = wp.i18n;

function Field({ data, error }) {
  switch (data.type) {
    case "number":
      return (
        <NumberField
          required={!!data.required}
          name={data.name}
          value={data.value}
          onChange={data.onChange}
          min={data.min}
          max={data.max}
          error={error}
        />
      );
    case "options":
      return (
        <OptionsField
          required={!!data.required}
          name={data.name}
          value={data.value}
          onChange={data.onChange}
          options={data.options}
        />
      );
    case "string":
    default:
      return (
        <TextField
          required={!!data.required}
          name={data.name}
          value={data.value}
          onChange={data.onChange}
          error={error}
        />
      );
  }
}

function TextField({ name, value, onChange, required, error }) {
  const constraints = {};
  if (required) constraints.required = true;

  const style = { width: "100%" };
  if (error) {
    style.border = "1px solid red";
  }

  return (
    <>
      <input
        type="text"
        name={name}
        value={value || ""}
        onChange={({ target }) => onChange(target.value)}
        style={style}
        {...constraints}
      />
      {error && <p style={{ color: "red" }}>{error}</p>}
    </>
  );
}

function NumberField({
  name,
  value,
  onChange,
  required,
  min = null,
  max = null,
  error,
}) {
  const constraints = {};
  if (min) constraints.min = min;
  if (max) constraints.max = max;
  if (required) constraints.required = true;

  if (!error && value) {
    if (isNaN(value)) {
      error = __("The value is not a number", "forms-bridge");
    } else {
      if (min !== null && !isNaN(min) && value < min) {
        error = __("The value is too small", "forms-bridge");
      } else if (max !== null && !isNaN(max) && value > max) {
        error = __("The value is too large", "forms-bridge");
      }
    }
  }
  const style = { width: "100%" };
  if (error) {
    style.border = "1px solid red";
  }

  return (
    <>
      <input
        type="number"
        name={name}
        value={value || ""}
        onChange={({ target }) => onChange(target.value)}
        style={style}
        {...constraints}
      />
      {error && <p style={{ color: "red" }}>{error}</p>}
    </>
  );
}

export default function TemplateField({ data, error }) {
  return (
    <label style={{ margin: "0.5rem 0" }} for={data.name}>
      {data.label}
      <br />
      <Field data={data} error={error} />
    </label>
  );
}
