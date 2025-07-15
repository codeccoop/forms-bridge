import JsonFinger from "./../../lib/JsonFinger";
import { useApiFields } from "../../providers/ApiSchema";
import { getFromOptions } from "./lib";
import DropdownSelect from "../DropdownSelect";

const { BaseControl, SelectControl, Button } = wp.components;
const { useEffect, useRef, useMemo, useState } = wp.element;
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
    value: "sum",
    label: __("Sum", "forms-bridge"),
  },
  {
    value: "count",
    label: __("Count", "forms-bridge"),
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

function useInputStyle(to = "", from = "") {
  const inputStyle = {
    height: "40px",
    paddingLeft: "12px",
    paddingRight: "12px",
    fontSize: "13px",
    borderRadius: "2px",
    display: "block",
    width: "100%",
  };

  if (to.length && !JsonFinger.validate(to)) {
    return { ...inputStyle, ...INVALID_TO_STYLE };
  }

  const isExpanded = /\[\]$/.test(from);

  const toExpansions = to.replace(/\[\]$/, "").match(/\[\]/g) || [];
  const fromExpansions = from.replace(/\[\]$/, "").match(/\[\]/g) || [];

  if ((isExpanded || !fromExpansions.length) && toExpansions > 1) {
    return { ...inputStyle, ...INVALID_TO_STYLE };
  } else if (
    fromExpansions.length &&
    toExpansions.length > fromExpansions.length
  ) {
    return { ...inputStyle, ...INVALID_TO_STYLE };
  }

  return inputStyle;
}

export default function MutationLayers({ fields, mappers, setMappers }) {
  const apiFields = useApiFields();

  const fieldOptions = useMemo(() => {
    // TODO: Use schemaToOptions to build a comprehensive list of api field options
    return apiFields.map((field) => ({
      value: field.name,
      label: `${field.name} | ${field.schema.type}`,
    }));
  }, [apiFields]);

  const tableWrapper = useRef();
  const [fieldSelector, setFieldSelector] = useState(-1);

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
      setTimeout(() => {
        if (!tableWrapper.current) return;

        tableWrapper.current.scrollTo(
          0,
          tableWrapper.current.children[0].offsetHeight
        );
      }, 100);
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
                style={{
                  textAlign: "left",
                  padding: "1em 0 0 0.5em",
                  columnWidth: "200px",
                }}
              >
                {__("From", "forms-bridge")}
              </th>
              <th
                scope="col"
                style={{
                  textAlign: "left",
                  padding: "1em 0 0 0.5em",
                  columnWidth: "200px",
                }}
              >
                {__("To", "forms-bridge")}
              </th>
              <th
                scope="col"
                style={{
                  textAlign: "left",
                  padding: "1em 0 0 0.5em",
                  columnWidth: "100px",
                }}
              >
                {__("Mutation", "forms-bridge")}
              </th>
              <th aria-hidden="true"></th>
            </tr>
          </thead>

          <tbody>
            {mappers.map(({ from, to, cast }, i) => {
              const fromOptions = getFromOptions(fields, mappers.slice(0, i));

              if (JsonFinger.isConditional(from)) {
                from = from.replace(/^\?/, "");
                if (!fromOptions.find((opt) => opt.value === from)) {
                  return null;
                }
              }

              return (
                <tr key={i}>
                  <td>{i + 1}.</td>
                  <td style={{ columnWidth: "200px" }}>
                    <SelectControl
                      value={from}
                      onChange={(value) => setMapper("from", i, value)}
                      options={fromOptions}
                      __nextHasNoMarginBottom
                      __next40pxDefaultSize
                    />
                  </td>
                  <td style={{ columnWidth: "200px" }}>
                    <div style={{ display: "flex" }}>
                      <div style={{ flex: 1 }}>
                        <BaseControl __nextHasNoMarginBottom>
                          <input
                            type="text"
                            value={to}
                            onChange={(ev) =>
                              setMapper("to", i, ev.target.value)
                            }
                            style={useInputStyle(to, from)}
                          />
                        </BaseControl>
                      </div>
                      <Button
                        style={{
                          height: "40px",
                          width: "40px",
                          justifyContent: "center",
                          marginLeft: "2px",
                        }}
                        disabled={fieldOptions.length === 0}
                        size="compact"
                        variant="secondary"
                        onClick={() => setFieldSelector(i)}
                        __next40pxDefaultSize
                      >
                        {"{...}"}
                        {fieldSelector === i && (
                          <DropdownSelect
                            title={__("Fields", "forms-bridge")}
                            tags={fieldOptions}
                            onChange={(fieldName) => {
                              setFieldSelector(-1);
                              setMapper("to", i, fieldName);
                            }}
                            onFocusOutside={() => setFieldSelector(-1)}
                          />
                        )}
                      </Button>
                    </div>
                  </td>
                  <td style={{ columnWidth: "100px" }}>
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
                        variant="secondary"
                        onClick={() => dropMapper(i)}
                        style={{
                          width: "40px",
                          height: "40px",
                          justifyContent: "center",
                        }}
                        isDestructive
                        __next40pxDefaultSize
                      >
                        -
                      </Button>
                    </div>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>
    </>
  );
}
