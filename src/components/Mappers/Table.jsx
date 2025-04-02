import JsonFinger from "./../../lib/JsonFinger";
import { getFromOptions } from "./lib";

const {
  SelectControl,
  TextControl,
  Button,
  __experimentalSpacer: Spacer,
} = wp.components;
const { useEffect, useRef } = wp.element;
const { __ } = wp.i18n;

const castOptions = [
  {
    value: "type-cast",
    label: __("Type mutations", "forms-bridge"),
    disabled: true,
  },
  {
    value: "string",
    label: __("String", "forms-bridge"),
  },
  {
    value: "integer",
    label: __("Integer", "forms-bridge"),
  },
  {
    value: "number",
    label: __("Decimal", "forms-bridge"),
  },
  {
    value: "boolean",
    label: __("Boolean", "forms-bridge"),
  },
  {
    value: "implode",
    label: __("Implode mutations", "forms-bridge"),
    disabled: true,
  },
  {
    value: "json",
    label: "JSON",
  },
  {
    value: "csv",
    label: "CSV",
  },
  {
    value: "concat",
    label: __("Concatenate", "forms-bridge"),
  },
  {
    value: "structure",
    label: __("Structure mutations", "forms-bridge"),
    disabled: true,
  },
  {
    value: "inherit",
    label: __("Rename", "forms-bridge"),
  },
  {
    value: "copy",
    label: __("Copy", "forms-bridge"),
  },
  {
    value: "null",
    label: __("Ignore", "forms-bridge"),
  },
];

const CSS = `.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
  overflow-y: auto;
  overflow-x: hidden;
}

.scrollbar-hide::-webkit-scrollbar {
  display: none;
}`;

const INVALID_TO_STYLE = {
  "--wp-components-color-accent": "#cc1818",
  "color":
    "var(--wp-components-color-accent, var(--wp-admin-theme-color, #3858e9))",
  "borderColor":
    "var(--wp-components-color-accent, var(--wp-admin-theme-color, #3858e9))",
};

function mapperToStyle(pointer = "") {
  if (pointer.length && !JsonFinger.validate(pointer, "set")) {
    return INVALID_TO_STYLE;
  }

  return {};
}

export default function MappersTable({
  title,
  fields,
  mappers,
  setMappers,
  done = null,
}) {
  const tableWrapper = useRef();

  const setMapper = (attr, index, value) => {
    const newMappers = mappers.map((mapper, i) => {
      if (index === i) {
        mapper[attr] = value;
        if (attr === "from" && mapper.to !== value) {
          mapper.to = value;
        }
      }
      return { ...mapper };
    });

    setMappers(newMappers);
  };

  const addMapper = () => {
    const newMappers = mappers.concat([{ from: "", to: "", cast: "string" }]);
    setTimeout(() => tableWrapper.current.scrollTo(0, 1e5), 100);
    setMappers(newMappers);
  };

  const dropMapper = (index) => {
    const newMappers = mappers.slice(0, index).concat(mappers.slice(index + 1));
    setMappers(newMappers);
  };

  useEffect(() => {
    if (!mappers.length) addMapper();
  }, [mappers]);

  const style = useRef(document.createElement("style"));
  useEffect(() => {
    style.current.appendChild(document.createTextNode(CSS));
    document.head.appendChild(style.current);

    return () => {
      document.head.removeChild(style.current);
    };
  }, []);

  return (
    <>
      <label
        className="components-base-control__label"
        style={{
          fontSize: "11px",
          textTransform: "uppercase",
          fontWeight: 500,
          lineHeight: "32px",
        }}
      >
        {title}
      </label>
      <div
        ref={tableWrapper}
        className="scrollbar-hide"
        style={{ flex: 1, borderTop: "2px outset", borderBottom: "2px inset" }}
      >
        <table
          style={{
            width: "calc(100% + 10px)",
            borderSpacing: "5px",
            margin: "0 -5px",
          }}
        >
          <tbody>
            {mappers.map(({ from, to, cast }, i) => (
              <tr key={i}>
                <td>
                  <SelectControl
                    placeholder={__("From", "forms-bridge")}
                    value={from}
                    onChange={(value) => setMapper("from", i, value)}
                    options={getFromOptions(fields, mappers.slice(0, i))}
                    __nextHasNoMarginBottom
                    __next40pxDefaultSize
                  />
                </td>
                <td>
                  <TextControl
                    placeholder={__("To", "forms-bridge")}
                    style={mapperToStyle(to)}
                    value={to}
                    onChange={(value) => setMapper("to", i, value)}
                    __nextHasNoMarginBottom
                    __next40pxDefaultSize
                  />
                </td>
                <td>
                  <SelectControl
                    placeholder={__("Cast as", "forms-bridge")}
                    value={cast || "string"}
                    onChange={(value) => setMapper("cast", i, value)}
                    options={castOptions}
                    __nextHasNoMarginBottom
                    __next40pxDefaultSize
                  />
                </td>
                <td>
                  <Button
                    isDestructive
                    variant="secondary"
                    onClick={() => dropMapper(i)}
                    __next40pxDefaultSize
                  >
                    {__("Drop", "forms-bridge")}
                  </Button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <Spacer paddingY="calc(3px)" />
      <div style={{ display: "flex", gap: "0.5rem" }}>
        <Button
          variant="secondary"
          onClick={() => addMapper()}
          __next40pxDefaultSize
        >
          {__("Add", "forms-bridge")}
        </Button>
        {done && (
          <Button
            variant="primary"
            onClick={() => done()}
            __next40pxDefaultSize
          >
            {__("Done", "forms-bridge")}
          </Button>
        )}
      </div>
    </>
  );
}
