const { useState } = wp.element;
const { Popover } = wp.components;

export default function DropdownSelect({
  title,
  tags,
  onChange,
  onFocusOutside,
}) {
  const [focus, setFocus] = useState(0);

  return (
    <Popover
      onFocusOutside={onFocusOutside}
      offset={5}
      placement="bottom-start"
    >
      <div
        style={{
          position: "relative",
          paddingTop: "2.6em",
          maxHeight: "350px",
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
        <ul
          id="bridge-tags-list"
          style={{
            width: "max-content",
            height: "100%",
            overflowY: "auto",
            margin: 0,
          }}
        >
          {tags.map(({ label, value }, i) => (
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
