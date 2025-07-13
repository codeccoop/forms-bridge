const { ToggleControl: Toggle } = wp.components;
const { useEffect, useRef } = wp.element;

const CSS = `.fb-toggle-control .components-form-toggle { height: 24px }
.fb-toggle-control .components-form-toggle__track { height: 24px; width: 48px; border-radius: 12px }
.fb-toggle-control .components-form-toggle__thumb { width: 18px; height: 18px; top: 3px; left: 3px }
.fb-toggle-control .components-form-toggle.is-checked .components-form-toggle__thumb { transform: translate(24px) }`;

export default function ToggleControl({
  checked,
  onChange,
  disabled,
  help,
  label,
}) {
  const style = useRef(document.createElement("style"));
  useEffect(() => {
    style.current.appendChild(document.createTextNode(CSS));
    document.head.appendChild(style.current);

    return () => {
      document.head.removeChild(style.current);
    };
  }, []);

  return (
    <Toggle
      label={label}
      help={help}
      className="fb-toggle-control"
      disabled={disabled}
      checked={!!checked}
      onChange={() => onChange(!!checked)}
      __nextHasNoMarginBottom
    />
  );
}
