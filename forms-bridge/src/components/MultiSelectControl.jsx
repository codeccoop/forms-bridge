const { useState, useEffect, useMemo, useRef } = wp.element;
const { SelectControl } = wp.components;

export default function MultiSelectControl({
  value = [],
  onChange = () => {},
  options = [],
  disabled = false,
}) {
  const [focus, setFocus] = useState(false);

  if (!Array.isArray(value)) {
    value = [value];
  }

  const label = useMemo(() => value.join(", "), [value]);

  const el = useRef();

  const onKeyDown = useRef((ev) => {
    if (ev.key !== "Enter") return;
    setFocus(false);
    document.body.removeEventListener("keydown", onKeyDown);
  }).current;

  const onClickOut = useRef((ev) => {
    if (ev.target !== el.current && !el.current.contains(ev.target)) {
      ev.preventDefault();
      setFocus(false);
      document.body.removeEventListener("click", onClickOut, true);
    }
  }).current;

  useEffect(() => {
    if (!focus) return;
    document.body.addEventListener("click", onClickOut, true);
    document.body.addEventListener("keydown", onKeyDown);
  }, [focus]);

  return (
    <div ref={el} style={{ position: "relative" }}>
      {(focus && (
        <div
          style={{
            position: "absolute",
            zIndex: 10,
            top: 0,
            left: 0,
            width: "100%",
            height: "100%",
          }}
        >
          <SelectControl
            disabled={disabled}
            value={value}
            onChange={onChange}
            options={options}
            multiple
            __nextHasNoMarginBottom
            __next40pxDefaultSize
          />
        </div>
      )) || (
        <>
          <input
            type="text"
            value={label}
            onChange={() => {}}
            style={{
              height: "40px",
              padding: "6px 30px 6px 12px",
              border: "1px solid #949494",
              borderRadius: "2px",
              cursor: "pointer",
              width: "100%",
            }}
            onClick={() => setFocus(true)}
          />
          <div
            style={{
              position: "absolute",
              zIndex: 1,
              right: "10px",
              top: "50%",
              width: "6px",
              height: "6px",
              borderRight: "1px solid",
              borderBottom: "1px solid",
              transform: "translate(-50%, -50%) scale(1, 0.80) rotate(45deg)",
              color: "black",
            }}
          ></div>
        </>
      )}
    </div>
  );
}
