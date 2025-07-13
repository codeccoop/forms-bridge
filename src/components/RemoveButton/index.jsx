const { useRef } = wp.element;
const { Button } = wp.components;
const { __ } = wp.i18n;

export function BinIcon() {
  return (
    <svg
      style={{ width: "12px" }}
      width="45.811565mm"
      height="67.009642mm"
      viewBox="0 0 45.811565 67.009642"
    >
      <g id="layer1" transform="translate(-40,-65.355415)">
        <rect
          style={{
            fill: "#ffffff",
            strokeWidth: 4.99998,
            strokeLinecap: "round",
            strokeLinejoin: "round",
            strokeMiterlimit: 9,
          }}
          id="rect234"
          width="45.811565"
          height="8.6819019"
          x="40"
          y="65.355415"
        />
        <path
          id="rect473"
          style={{
            fill: "#ffffff",
            strokeWidth: 4.99998,
            strokeLinecap: "round",
            strokeLinejoin: "round",
            strokeMiterlimit: 9,
          }}
          d="m 40.500274,75.758567 3.276235,51.199693 h 0.01306 c 0.252952,3.00397 2.522367,5.4068 5.552375,5.4068 h 26.954544 c 3.030011,0 5.299425,-2.40283 5.552376,-5.4068 h 0.01305 L 85.138155,75.758567 H 79.026688 46.611752 Z"
        />
      </g>
    </svg>
  );
}

export default function RemoveButton({
  onClick,
  variant = "primary",
  isDestructive = true,
  disabled = false,
  size = "default",
  style = {},
  children,
  icon = false,
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
      isDestructive={isDestructive}
      variant={variant}
      onClick={doubleClickAlert}
      onDoubleClick={(ev) => {
        onClick(ev);
        clearTimeout(alertDelay.current);
      }}
      style={style}
      showTooltip={true}
      label={__("Double click to remove", "forms-bridge")}
      disabled={disabled}
      // size={size}
      __next40pxDefaultSize
    >
      {(icon && <BinIcon />) || children}
    </Button>
  );
}
