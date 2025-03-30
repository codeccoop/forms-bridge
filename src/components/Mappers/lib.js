import JsonFinger from "./JsonFinger";

const cache = new WeakMap();

function payloadToOptions(payload, fields, mappers) {
  return Object.keys(payload).reduce((options, key) => {
    let sKey;
    if (Array.isArray(payload)) {
      sKey = +key;
    } else {
      sKey = JsonFinger.sanitizeKey(key);
    }

    options.push({ value: sKey, label: sKey });

    if (Array.isArray(payload[key])) {
      const field = fields.find((field) => {
        let name = field.name;
        mappers.forEach(({ from, to }) => {
          if (from === name) {
            name = to;
          }
        });

        return name === key;
      });

      if (!field || !field.schema.additionalItems) {
        payload[key].forEach((item, i) => {
          if (typeof item === "string") {
            options.push({
              value: `${sKey}[${i}]`,
              label: `${sKey}[${i}]`,
            });
          } else {
            options = options.concat(
              payloadToOptions(item, fields, mappers).map((opt) => {
                let value = opt.value;
                if (+value === value) {
                  value = `${sKey}[${i}][${value}]`;
                } else {
                  if (value[0] === "[") {
                    value = `${sKey}[${i}]${value}`;
                  } else {
                    value = `${sKey}[${i}].${value}`;
                  }
                }

                return { value, label: value };
              })
            );
          }
        });
      }
    } else if (payload[key] && typeof payload[key] === "object") {
      options = options.concat(
        payloadToOptions(payload[key], fields, mappers).map((opt) => {
          let value = opt.value;
          if (+value === value) {
            value = `${sKey}[${value}]`;
          } else {
            if (value[0] === "[") {
              value = `${sKey}${value}`;
            } else {
              value = `${sKey}.${value}`;
            }
          }

          return { value, label: value };
        })
      );
    }

    return options;
  }, []);
}

function schemaToPayload(schema, pointer) {
  if (schema.type === "object") {
    pointer = JsonFinger.parse(pointer);

    return Object.keys(schema.properties).reduce((payload, prop) => {
      payload[prop] = schemaToPayload(
        schema.properties[prop],
        JsonFinger.pointer(pointer.concat(prop))
      );

      return payload;
    }, {});
  } else if (schema.type === "array") {
    const items = schema.maxItems || schema.minItems || 1;

    return Array.from(Array(items)).reduce((payload, _, i) => {
      const itemPointer = `${pointer}[${i}]`;
      return payload.concat(schemaToPayload(schema.items, itemPointer));
    }, []);
  }

  return schema.type;
}

export function applyMappers(payload, mappers) {
  const finger = new JsonFinger(payload);

  for (const mapper of mappers) {
    const isValid =
      JsonFinger.validate(mapper.from) && JsonFinger.validate(mapper.to);

    if (!isValid) {
      continue;
    }

    const isset = finger.isset(mapper.from);
    if (!isset) {
      continue;
    }

    const value = finger.get(mapper.from);

    if (
      (mapper.cast !== "copy" && mapper.from !== mapper.to) ||
      mapper.cast === "null"
    ) {
      finger.unset(mapper.from);
    }

    if (mapper.cast !== "null") {
      finger.set(mapper.to, castValue(mapper.cast, value));
    }
  }

  return finger.data;
}

export function fieldsToPayload(fields) {
  if (cache.has(fields)) {
    return cache.get(fields);
  }

  const finger = new JsonFinger({});

  fields.forEach(({ name, schema }) => {
    const nameKeys = JsonFinger.parse(name);
    const pointer = JsonFinger.pointer(nameKeys);
    finger.set(pointer, schemaToPayload(schema, pointer));
  });

  cache.set(fields, finger.data);
  return finger.data;
}

export function getFromOptions(fields, mappers) {
  const payload = applyMappers(fieldsToPayload(fields), mappers);
  const options = payloadToOptions(payload, fields, mappers);
  return [{ label: "", value: "" }].concat(options);
}

export function castValue(cast, from) {
  switch (cast) {
    case "json":
    case "concat":
    case "csv":
      return "string";
    case "copy":
    case "inherit":
      return JSON.parse(JSON.stringify(from));
    default:
      return cast;
  }
}
