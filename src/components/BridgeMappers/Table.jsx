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

function buildFinger(keys) {
  return keys
    .reduce((finger, key) => {
      const isArray = +key === key;
      if (isArray) {
        if (key === -1) {
          key = "";
        }

        key = `[${key}]`;
      } else {
        key = "." + key;
      }

      return finger + key;
    }, "")
    .slice(1);
}

const fingerCache = new Map();

function parseFinger(finger) {
  if (fingerCache.has(finger)) {
    return fingerCache.get(finger);
  }

  const len = finger.length;
  const keys = [];
  let key = "";
  let closured = false;
  let index = 0;

  // Parse finger as a charstring iteration
  for (let i = 0; i < len; i++) {
    const char = finger[i];
    if (closured) {
      if (char === '"') {
        closured = false;
      } else {
        key += char;
      }
    } else {
      if (char === '"') {
        closured = true;
      } else if (char === ".") {
        keys.push(key);
        key = "";
      } else if (char === "[") {
        keys.push(key);
        key = "";

        i = from = i + 1;
        index = "";
        while (finger[i] !== "]" && i < len) {
          index += finger[i];
          i += 1;
        }

        if (index.length === 0) {
          index = -1;
        } else if (isNaN(index)) {
          fingerCache.set(finger, [finger]);
          return [finger];
        }

        index = +index;
        keys.push(index);
        i += 1;
        if (finger.length > i) {
          if (finger[i] !== ".") {
            fingerCache.set(finger, [finger]);
            return [finger];
          }
        }
      } else {
        key += char;
      }
    }
  }

  if (key) {
    keys.push(key);
  }

  fingerCache.set(finger, keys);
  return keys;
}

function chainedFromOptions(options, mappers, index) {
  const mutations = mappers.slice(0, index);

  const uniques = new Set();
  const mutatedOptions = options
    .reduce((options, opt) => {
      opt = { ...opt };
      mutations.forEach((mutation) => {
        if (mutation.from === opt.value) {
          opt.value = mutation.cast === "null" ? null : mutation.to;
          if (opt.value !== null) {
            opt.label = opt.value;
          }
        }
      });

      if (opt.value === null) {
        return options;
      }

      const repetitions = options.filter(({ value }) => value === opt.value);
      if (repetitions.length) {
        if (/\[\]$/.test(opt.value)) {
          mappers
            .filter(({ to }) => to === opt.value)
            .forEach((_, i) => {
              const finger = opt.value.replace(/\[\]$/, "");
              const value = `${finger}[${i}]`;

              if (!uniques.has(value)) {
                uniques.add(value);
                options.push({ value, label: value });
              }
            });
        }
      } else {
        uniques.add(opt.value);
        options.push(opt);
      }

      return options;
    }, [])
    .filter((opt) => !opt.value.endsWith("[]"));

  return mutatedOptions
    .filter((opt) => {
      const keys = parseFinger(opt.value);
      if (keys.length === 1) {
        return true;
      }

      for (let i = 0; i < keys.length; i++) {
        const finger = buildFinger(keys.slice(0, i + 1));

        if (mutations.find(({ from }) => from === finger)) {
          return;
        }
      }

      return true;
    })
    .concat(fingerOptions(mutatedOptions, mutations));
}

function fingerOptions(options, mutations) {
  console.log(mutations);
  const values = new Set(options.map((opt) => opt.value));
  const uniques = new Set();

  return options
    .map((opt) => parseFinger(opt.value))
    .filter((keys) => keys.length > 1)
    .reduce((options, keys) => {
      keys.forEach((_, i, keys) => {
        const fingerKeys = keys.slice(0, i + 1);
        let finger = buildFinger(fingerKeys);

        if (!values.has(finger) && !uniques.has(finger)) {
          uniques.add(finger);
          options.push({ value: finger, label: finger });
        }
      });

      return options;
    }, [])
    .map((opt) => {
      mutations.forEach((mutation) => {
        if (mutation.from === opt.value) {
          if (mutation.cast === "null") {
            opt.value = null;
          } else {
            opt.value = mutation.to;
          }

          opt.label = opt.value;
        }
      });

      return opt;
    })
    .filter((opt) => opt.value);
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
