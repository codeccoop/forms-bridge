const { useState, useEffect, useMemo, useRef } = wp.element;
const { Popover } = wp.components;

export default function DropdownSelect({
  open,
  title,
  tags,
  onChange,
  onRequestClose,
}) {
  const [focus, setFocus] = useState(0);

  const [pattern, setPattern] = useState("");
  const searchRef = useRef();

  useEffect(() => {
    if (!searchRef.current) return;
    searchRef.current.focus();
  }, [open]);

  useEffect(() => {
    if (!open) return;

    const onKeyDown = (ev) => {
      if (ev.key === "Escape" && open) {
        onRequestClose();
      }
    };

    document.body.addEventListener("keydown", onKeyDown);

    return () => {
      document.body.removeEventListener("keydown", onKeyDown);
    };
  }, [open]);

  const filteredTags = useMemo(() => {
    if (!pattern) return tags;

    const tokens = pattern
      .toLowerCase()
      .split(" ")
      .map((token) => token.trim());

    return tags.filter((tag) => {
      return tokens.find((token) => tag.value.toLowerCase().includes(token));
    });
  }, [tags, pattern]);

  if (!open) return;

  return (
    <Popover
      onFocusOutside={onRequestClose}
      offset={5}
      placement="bottom-start"
    >
      <div
        style={{
          position: "relative",
          marginTop: "62px",
          minWidth: "300px",
        }}
      >
        <label
          htmlFor="bridge-tags-list"
          style={{
            position: "fixed",
            top: "0px",
            left: "0px",
            width: "100%",
            padding: "0.5em 0.75em",
            borderBottom: "1px solid",
            backgroundColor: "white",
          }}
        >
          <strong>{title}</strong>
        </label>
        <input
          name="dropdown-select"
          ref={searchRef}
          type="text"
          value={pattern}
          onChange={(ev) => setPattern(ev.target.value)}
          style={{
            position: "fixed",
            top: "32.2px",
            left: 0,
            boxShadow: "none",
            outline: "none",
            border: "none",
            borderRadius: 0,
            borderBottom: "1px solid",
            width: "100%",
          }}
        />
        <ul
          id="bridge-tags-list"
          style={{
            height: "100%",
            overflowY: "auto",
            margin: 0,
            maxHeight: "300px",
            width: "100%",
          }}
        >
          {filteredTags.map(({ label, value }, i) => (
            <li
              key={label}
              style={{ padding: "0.5em 1em", cursor: "pointer" }}
              tabIndex="0"
              role="button"
              onKeyDown={(ev) => {
                if (focus !== i) return;

                if (ev.key === "Enter") {
                  ev.stopPropagation();
                  ev.preventDefault();
                  onChange(value);
                }
              }}
              onFocus={() => setFocus(i)}
              onClick={(ev) => {
                ev.preventDefault();
                ev.stopPropagation();
                onChange(value);
              }}
            >
              {label}
            </li>
          ))}
        </ul>
      </div>
    </Popover>
  );
}
