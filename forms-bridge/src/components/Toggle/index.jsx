const { ToggleControl: Toggle } = wp.components;
const { useEffect, useRef } = wp.element;

const CSS = `.fb-toggle-control .components-form-toggle { height: 24px }
.fb-toggle-control .components-form-toggle__track { height: 24px; width: 48px; border-radius: 12px }
.fb-toggle-control .components-form-toggle__thumb { width: 18px; height: 18px; top: 3px; left: 3px }
.fb-toggle-control .components-form-toggle.is-checked .components-form-toggle__thumb { transform: translate(24px) }
.fb-toggle-control .components-toggle-control__help { margin-inline-start: 56px }`;

export default function ToggleControl({
  checked,
  onChange,
  disabled,
  help,
  label,
  noEdit = false,
}) {
  const style = useRef(document.createElement("style"));
  useEffect(() => {
    let css = CSS;

    if (noEdit) {
      css +=
        ".fb-toggle-control .components-form-toggle.is-disabled { opacity: 1 }";
    }

    style.current.appendChild(document.createTextNode(css));
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
