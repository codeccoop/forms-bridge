import WorkflowStageField from "./StageField";

const { Tooltip } = wp.components;
const { __ } = wp.i18n;

const BASE = {
  color: "#2f2f2f",
  background: "#f0f0f0",
  icon: null,
};

const CHECK = {
  color: "#4ab866",
  background: "color-mix(in srgb, #fff 90%, #4ab866)",
  icon: (
    <svg
      xmlns="http://www.w3.org/2000/svg"
      viewBox="0 0 24 24"
      width="16"
      height="16"
      fill="currentColor"
      class="components-badge__icon"
      aria-hidden="true"
      focusable="false"
    >
      <path
        fill-rule="evenodd"
        clip-rule="evenodd"
        d="M12 18.5a6.5 6.5 0 1 1 0-13 6.5 6.5 0 0 1 0 13ZM4 12a8 8 0 1 1 16 0 8 8 0 0 1-16 0Zm11.53-1.47-1.06-1.06L11 12.94l-1.47-1.47-1.06 1.06L11 15.06l4.53-4.53Z"
      ></path>
    </svg>
  ),
};

const WARN = {
  color: "#f0b849",
  background: "color-mix(in srgb, #fff 90%, #f0b849)",
  icon: (
    <svg
      viewBox="0 0 24 24"
      xmlns="http://www.w3.org/2000/svg"
      width="16"
      height="16"
      fill="currentColor"
      class="components-badge__icon"
      aria-hidden="true"
      focusable="false"
    >
      <path
        fill-rule="evenodd"
        clip-rule="evenodd"
        d="M5.5 12a6.5 6.5 0 1 0 13 0 6.5 6.5 0 0 0-13 0ZM12 4a8 8 0 1 0 0 16 8 8 0 0 0 0-16Zm-.75 12v-1.5h1.5V16h-1.5Zm0-8v5h1.5V8h-1.5Z"
      ></path>
    </svg>
  ),
};

const ALERT = {
  color: "#cc1818",
  background: "color-mix(in srgb, #fff 90%, #cc1818)",
  icon: (
    <svg
      viewBox="0 0 24 24"
      xmlns="http://www.w3.org/2000/svg"
      width="16"
      height="16"
      fill="currentColor"
      class="components-badge__icon"
      aria-hidden="true"
      focusable="false"
    >
      <path
        fill-rule="evenodd"
        clip-rule="evenodd"
        d="M12.218 5.377a.25.25 0 0 0-.436 0l-7.29 12.96a.25.25 0 0 0 .218.373h14.58a.25.25 0 0 0 .218-.372l-7.29-12.96Zm-1.743-.735c.669-1.19 2.381-1.19 3.05 0l7.29 12.96a1.75 1.75 0 0 1-1.525 2.608H4.71a1.75 1.75 0 0 1-1.525-2.608l7.29-12.96ZM12.75 17.46h-1.5v-1.5h1.5v1.5Zm-1.5-3h1.5v-5h-1.5v5Z"
      ></path>
    </svg>
  ),
};

function InputField({ data }) {
  const { name, schema, missing, mutated, optional } = data;
  const style = missing ? ALERT : mutated ? WARN : optional ? BASE : CHECK;

  const feedback = missing
    ? __("Field is required", "forms-bridge")
    : mutated
      ? __("Field type mutation", "forms-bridge")
      : optional
        ? __("Field is optional or type not match", "forms-bridge")
        : "";

  return (
    <Tooltip text={feedback}>
      <span
        style={{
          cursor: "pointer",
          color: style.color,
          backgroundColor: style.background,
          padding: "0 8px",
          borderRadius: "2px",
          fontSize: "12px",
          lineHeight: "20px",
          alignItems: "center",
          display: "inline-flex",
          gap: "2px",
        }}
      >
        {style.icon}
        <WorkflowStageField
          name={name}
          schema={schema}
          showDiff={false}
          enter={false}
          exit={false}
          mutated={false}
        />
      </span>
    </Tooltip>
  );
}

export default function WorkflowStageInterface({ fields }) {
  return (
    <div style={{ display: "flex", gap: "5px", flexWrap: "wrap" }}>
      <strong>{__("Job interface", "forms-bridge")}:&nbsp;</strong>
      {fields.map((field) => (
        <InputField key={field.name} data={field} />
      ))}
    </div>
  );
}
