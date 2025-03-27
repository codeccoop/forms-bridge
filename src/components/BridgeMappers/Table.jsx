import JsonFinger from "./JsonFinger";

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
    label: __("String", "forms-bridge"),
  },
  {
    value: "integer",
    label: __("Integer", "forms-bridge"),
  },
  {
    value: "float",
    label: __("Decimal", "forms-bridge"),
  },
  {
    value: "boolean",
    label: __("Boolean", "forms-bridge"),
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
    value: "copy",
    label: __("Copy", "forms-bridge"),
  },
  {
    value: "null",
    label: __("Ignore", "forms-bridge"),
  },
];

function chainedFromOptions(options, mappers, index) {
  const mutations = mappers.slice(0, index);

  const uniques = new Set();
  const mutatedOptions = options
    .reduce((options, opt) => {
      opt = { ...opt };
      mutations.forEach((mutation, i) => {
        if (mutation.from === opt.value) {
          if (mutation.cast === "copy" && mutation.to !== opt.value) {
            const ignoredAfter =
              mutations.slice(i + 1).find(({ to }) => to === opt.value)
                ?.cast === "null";

            if (!ignoredAfter) {
              options.push({ ...opt });
            }
          }

          opt.value = mutation.cast === "null" ? null : mutation.to;
          if (opt.value !== null) {
            opt.label = opt.value;
          }
        }
      });

      if (opt.value === null) {
        return options;
      }

      const arrayItems = opt.value.endsWith("[]")
        ? options.filter(({ value }) => value === opt.value)
        : [];

      if (arrayItems.length) {
        mappers
          .filter(({ to }) => to === opt.value)
          .forEach((_, i) => {
            const finger = opt.value.slice(0, -2);
            const value = `${finger}[${i}]`;

            if (!uniques.has(value)) {
              uniques.add(value);
              options.push({ value, label: value });
            }
          });
      } else {
        uniques.add(opt.value);
        options.push(opt);
      }

      return options;
    }, [])
    .filter((opt) => !opt.value.endsWith("[]"));

  console.log(fingerOptions(mutatedOptions, mutations));
  return mutatedOptions
    .filter((opt) => {
      const keys = JsonFinger.parse(opt.value);
      if (keys.length === 1) {
        return true;
      }

      for (let i = 0; i < keys.length; i++) {
        const finger = JsonFinger.build(keys.slice(0, i + 1));

        const mutation = mutations.find(({ from }) => from === finger);
        if (mutation && mutation.cast !== "copy") {
          return false;
        }
      }

      return true;
    })
    .concat(fingerOptions(mutatedOptions, mutations));
}

function fingerOptions(options, mutations) {
  const values = new Set(options.map((opt) => opt.value));
  const uniques = new Set();

  return options
    .map((opt) => JsonFinger.parse(opt.value))
    .filter((keys) => keys.length > 1)
    .reduce((options, keys) => {
      keys.forEach((_, i, keys) => {
        const fingerKeys = keys.slice(0, i + 1);
        let finger = JsonFinger.build(fingerKeys);

        if (!values.has(finger) && !uniques.has(finger)) {
          uniques.add(finger);
          options.push({ value: finger, label: finger });
        }
      });

      return options;
    }, [])
    .reduce((options, opt) => {
      mutations.forEach((mutation, i) => {
        if (mutation.from === opt.value) {
          if (mutation.cast === "copy" && mutation.to !== opt.value) {
            const ignoredAfter =
              mutations.slice(i + 1).find(({ to }) => to === opt.value)
                ?.cast === "null";

            if (!ignoredAfter) {
              options.push({ ...opt });
            }
          }

          if (mutation.cast === "null") {
            opt.value = null;
          } else {
            opt.value = mutation.to;
          }

          opt.label = opt.value;
        }
      });

      if (opt.value === null) {
        return options;
      }

      options.push(opt);
      return options;
    }, []);
}

export default function MappersTable({ form, mappers, setMappers, done }) {
  const fields = useMemo(() => {
    if (!form) return [];
    return form.fields
      .filter(({ is_file }) => !is_file)
      .map(({ name, label }) => ({ name, label }));
  }, [form]);

  const fromOptions = useMemo(
    () =>
      [{ label: "", value: "" }].concat(
        fields.map((field) => ({
          label: field.label,
          value: field.name,
        }))
      ),
    [fields]
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
                  options={chainedFromOptions(fromOptions, mappers, i)}
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
