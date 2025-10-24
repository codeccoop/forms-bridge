import PayloadField from "./PayloadField";

const { useState, useEffect, useRef } = wp.element;
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
      className="components-badge__icon"
      aria-hidden="true"
      focusable="false"
    >
      <path
        fillRule="evenodd"
        clipRule="evenodd"
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
      className="components-badge__icon"
      aria-hidden="true"
      focusable="false"
    >
      <path
        fillRule="evenodd"
        clipRule="evenodd"
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
      className="components-badge__icon"
      aria-hidden="true"
      focusable="false"
    >
      <path
        fillRule="evenodd"
        clipRule="evenodd"
        d="M12.218 5.377a.25.25 0 0 0-.436 0l-7.29 12.96a.25.25 0 0 0 .218.373h14.58a.25.25 0 0 0 .218-.372l-7.29-12.96Zm-1.743-.735c.669-1.19 2.381-1.19 3.05 0l7.29 12.96a1.75 1.75 0 0 1-1.525 2.608H4.71a1.75 1.75 0 0 1-1.525-2.608l7.29-12.96ZM12.75 17.46h-1.5v-1.5h1.5v1.5Zm-1.5-3h1.5v-5h-1.5v5Z"
      ></path>
    </svg>
  ),
};

function InputField({ data }) {
  const { name, schema, required, missing, mutated, optional } = data;
  const style = missing ? ALERT : mutated ? WARN : optional ? BASE : CHECK;

  const feedback = missing
    ? __("Field is required", "forms-bridge")
    : mutated
      ? __("Field type mutation", "forms-bridge")
      : optional
        ? __("Field is optional", "forms-bridge")
        : "";

  const displayName = required ? name + "∗" : name;

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
        <PayloadField
          name={displayName}
          schema={schema}
          showDiff={false}
          enter={false}
          exit={false}
          mutated={false}
          simple={true}
        />
      </span>
    </Tooltip>
  );
}

export default function WorkflowJobInterface({
  fields,
  collapsible = true,
  inline = false,
}) {
  const wrapper = useRef();

  const [overflow, setOverflow] = useState(false);
  const [expanded, setExpanded] = useState(false);

  useEffect(() => {
    if (!wrapper.current) return;
    setOverflow(wrapper.current.offsetHeight > 20);
  }, [fields]);

  return (
    <div
      style={{
        height: !collapsible || expanded ? "auto" : "20px",
        overflow: "hidden",
        paddingRight: "10px",
        position: "relative",
      }}
    >
      <div
        ref={wrapper}
        style={{
          display: inline ? "block" : "flex",
          gap: "5px",
          flexWrap: "wrap",
        }}
        onClick={() => setExpanded(!expanded)}
      >
        {(inline && (
          <strong>{__("Job interface", "forms-bridge")}:&nbsp;</strong>
        )) || (
          <p style={{ margin: 0 }}>
            {__("Job interface", "forms-bridge")}:&nbsp;
          </p>
        )}
        {(fields.length &&
          fields.map((field) => (
            <InputField key={field.name} data={field} />
          ))) ||
          null}
      </div>
      {(overflow && collapsible && (
        <button
          style={{
            position: "absolute",
            top: "10px",
            right: "0px",
            height: "20px",
            width: "20px",
            transform: "translateY(-50%)",
            backgroundColor: "transparent",
            border: "none",
            cursor: "pointer",
          }}
          onClick={() => setExpanded(!expanded)}
        >
          <span
            style={{
              display: "block",
              width: "6px",
              height: "6px",
              borderRight: "2px solid",
              borderBottom: "2px solid",
              transition: "transform 200ms ease",
              transform: expanded
                ? "translateY(1.5px) rotate(-135deg)"
                : "translateY(-1.5px) rotate(45deg)",
            }}
          ></span>
        </button>
      )) ||
        null}
    </div>
  );
}
