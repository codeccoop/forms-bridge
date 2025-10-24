import BinIcon from "../icons/Bin";

const { useRef } = wp.element;
const { Button } = wp.components;
const { __ } = wp.i18n;

export default function RemoveButton({
  onClick,
  variant = "primary",
  isDestructive = true,
  disabled = false,
  size = "default",
  style = {},
  children,
  icon = false,
  label,
}) {
  style = { justifyContent: "center", ...style };

  if (size == "compact") {
    style.width = "40px";
  }

  const alertDelay = useRef();
  function doubleClickAlert() {
    clearTimeout(alertDelay.current);
    alertDelay.current = setTimeout(
      () => alert(__("Double click to remove", "forms-bridge")),
      300
    );
  }

  return (
    <Button
      variant={variant}
      onClick={doubleClickAlert}
      onDoubleClick={(ev) => {
        onClick(ev);
        clearTimeout(alertDelay.current);
        window.__wpfbInvalidated = !!isDestructive;
      }}
      style={style}
      showTooltip={true}
      label={label || __("Double click to remove", "forms-bridge")}
      disabled={disabled}
      isDestructive
      __next40pxDefaultSize
    >
      {(icon && (
        <div style={{ opacity: disabled ? 0.5 : 1 }}>
          <BinIcon width="12" height="20" />
        </div>
      )) ||
        children}
    </Button>
  );
}
