const { useEffect } = wp.element;
const { __ } = wp.i18n;

function Field({ data, error }) {
  switch (data.type) {
    case "boolean":
      return (
        <CheckboxField
          name={data.name}
          value={data.value}
          onChange={data.onChange}
        />
      );
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
          multiple={!!data.multiple}
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

function CheckboxField({ name, value, onChange }) {
  if (Array.isArray(value)) {
    value = !!value[0];
  }

  return (
    <input
      type="checkbox"
      name={name}
      onChange={() => onChange(!value)}
      checked={value}
    />
  );
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

function OptionsField({
  name,
  value,
  onChange,
  required,
  multiple,
  options,
  error,
}) {
  const constraints = {};
  if (required) constraints.required = true;

  const style = { width: "100%" };
  if (error) {
    style.border = "1px solid red";
  }

  useEffect(() => {
    if (!options.length) return;
    onChange(options[0].value);
  }, [options]);

  return (
    <>
      <select
        name={name}
        value={value || ""}
        onChange={({ target }) => {
          const value = Array.from(target.children)
            .filter((opt) => opt.selected)
            .map((opt) => opt.value);
          if (multiple) {
            onChange(value);
          } else {
            onChange(value[0]);
          }
        }}
        style={style}
        multiple={!!multiple}
        {...constraints}
      >
        {options.map(({ label, value }) => (
          <option value={value}>{label}</option>
        ))}
      </select>
    </>
  );
}

export default function TemplateField({ data, error }) {
  const isRequired = !!data.required;
  return (
    <label style={{ margin: "0.5rem 0" }} for={data.name}>
      {data.label}
      {isRequired && <span style={{ marginLeft: "3px", color: "red" }}>*</span>}
      {(data.description && (
        <p style={{ marginTop: 0, opacity: 0.8 }}>
          <em dangerouslySetInnerHTML={{ __html: data.description }}></em>
        </p>
      )) || <br />}
      <Field data={data} error={error} />
    </label>
  );
}
