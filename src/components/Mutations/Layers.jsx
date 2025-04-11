import JsonFinger from "./../../lib/JsonFinger";
import { getFromOptions } from "./lib";

const { SelectControl, TextControl, Button } = wp.components;
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
    label: __("Spaced", "forms-bridge"),
  },
  {
    value: "join",
    label: __("Join", "forms-bridge"),
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
}

.scrollbar-hide table tr td {
  padding: 1em 0.25em;
}

.scrollbar-hide table tr td:first-child {
  padding: 1em 0.5em 1em 5px;
}

.scrollbar-hide table tr td:last-child {
  padding: 1em 10px 1em 0.25em;
  white-space: nowrap;
}

.scrollbar-hide table tr:not(:last-child) td {
  border-bottom: 1px solid #ccc;
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

export default function MutationLayers({ fields, mappers, setMappers }) {
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

  const addMapper = (index) => {
    const newMappers = mappers
      .slice(0, index)
      .concat([{ from: "", to: "", cast: "string" }])
      .concat(mappers.slice(index, mappers.length));

    if (index === mappers.length) {
      setTimeout(
        () =>
          tableWrapper.current.scrollTo(0, tableWrapper.current.offsetHeight),
        100
      );
    }

    setMappers(newMappers);
  };

  const dropMapper = (index) => {
    const newMappers = mappers.slice(0, index).concat(mappers.slice(index + 1));
    setMappers(newMappers);
  };

  useEffect(() => {
    if (!mappers.length) addMapper(mappers.length);
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
      <div ref={tableWrapper} className="scrollbar-hide" style={{ flex: 1 }}>
        <table
          style={{
            width: "calc(100% + 10px)",
            margin: "0 -5px",
            borderSpacing: "0px",
          }}
        >
          <thead>
            <tr>
              <th aria-hidden="true"></th>
              <th
                scope="col"
                style={{ textAlign: "left", padding: "1em 0 0 0.5em" }}
              >
                {__("From", "forms-bridge")}
              </th>
              <th
                scope="col"
                style={{ textAlign: "left", padding: "1em 0 0 0.5em" }}
              >
                {__("To", "forms-bridge")}
              </th>
              <th
                scope="col"
                style={{ textAlign: "left", padding: "1em 0 0 0.5em" }}
              >
                {__("Mutation", "forms-bridge")}
              </th>
              <th aria-hidden="true"></th>
            </tr>
          </thead>

          <tbody>
            {mappers.map(({ from, to, cast }, i) => (
              <tr key={i}>
                <td>{i + 1}.</td>
                <td>
                  <SelectControl
                    value={from}
                    onChange={(value) => setMapper("from", i, value)}
                    options={getFromOptions(fields, mappers.slice(0, i))}
                    __nextHasNoMarginBottom
                    __next40pxDefaultSize
                  />
                </td>
                <td>
                  <TextControl
                    style={mapperToStyle(to)}
                    value={to}
                    onChange={(value) => setMapper("to", i, value)}
                    __nextHasNoMarginBottom
                    __next40pxDefaultSize
                  />
                </td>
                <td>
                  <SelectControl
                    value={cast || "string"}
                    onChange={(value) => setMapper("cast", i, value)}
                    options={castOptions}
                    __nextHasNoMarginBottom
                    __next40pxDefaultSize
                  />
                </td>
                <td>
                  <div
                    style={{
                      display: "flex",
                      marginLeft: "0.45em",
                      gap: "0.45em",
                    }}
                  >
                    <Button
                      size="compact"
                      variant="secondary"
                      disabled={!to || !from}
                      onClick={() => addMapper(i + 1)}
                      style={{
                        width: "40px",
                        height: "40px",
                        justifyContent: "center",
                      }}
                      __next40pxDefaultSize
                    >
                      +
                    </Button>
                    <Button
                      size="compact"
                      isDestructive
                      variant="secondary"
                      onClick={() => dropMapper(i)}
                      style={{
                        width: "40px",
                        height: "40px",
                        justifyContent: "center",
                      }}
                      __next40pxDefaultSize
                    >
                      -
                    </Button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </>
  );
}
