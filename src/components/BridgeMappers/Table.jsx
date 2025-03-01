const {
  SelectControl,
  TextControl,
  Button,
  __experimentalSpacer: Spacer,
} = wp.components;
const { useEffect, useMemo } = wp.element;
const { __ } = wp.i18n;

const castOptions = [
  {
    value: "string",
    label: "String",
  },
  {
    value: "integer",
    label: "Integer",
  },
  {
    value: "float",
    label: "Decimal",
  },
  {
    value: "boolean",
    label: "Boolean",
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
    label: "Concatenate",
  },
  {
    value: "null",
    label: "Ignore",
  },
];

export default function MappersTable({ form, mappers, setMappers, done }) {
  const fields = useMemo(() => {
    if (!form) return [];
    return form.fields
      .filter(({ is_file }) => !is_file)
      .map(({ name, label }) => ({ name, label }));
  }, [form]);

  const fromOptions = [{ label: "", value: "" }].concat(
    fields.map((field) => ({
      label: __(field.label, "forms-bridge"),
      value: field.name,
    }))
  );

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
    setMappers(newMappers);
  };

  const dropMapper = (index) => {
    const newMappers = mappers.slice(0, index).concat(mappers.slice(index + 1));
    setMappers(newMappers);
  };

  useEffect(() => {
    if (!mappers.length) addMapper();
  }, [mappers]);

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
        {__("Form mapper", "forms-bridge")}
      </label>
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
                  options={fromOptions}
                  __nextHasNoMarginBottom
                  __next40pxDefaultSize
                />
              </td>
              <td>
                <TextControl
                  placeholder={__("To", "forms-bridge")}
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
                  options={castOptions.map(({ label, value }) => ({
                    label: __(label, "forms-bridge"),
                    value,
                  }))}
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
      <Spacer paddingY="calc(3px)" />
      <div style={{ display: "flex", gap: "0.5rem" }}>
        <Button
          variant="secondary"
          onClick={() => addMapper()}
          __next40pxDefaultSize
        >
          {__("Add", "forms-bridge")}
        </Button>
        <Button variant="primary" onClick={() => done()} __next40pxDefaultSize>
          {__("Done", "forms-bridge")}
        </Button>
      </div>
    </>
  );
}
