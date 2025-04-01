import JsonFinger from "./JsonFinger";

const cache = new WeakMap();

export function clonePayload(payload) {
  if (!payload) return payload;

  let clone;
  if (Array.isArray(payload)) {
    clone = payload.map(clonePayload);
  } else if (typeof payload === "object") {
    clone = Object.keys(payload).reduce((clone, key) => {
      clone[key] = clonePayload(payload[key]);
      return clone;
    }, {});
  } else {
    clone = payload;
  }

  if (Object.isFrozen(payload)) {
    Object.freeze(clone);
  }

  return clone;
}

export function payloadToOptions(payload, mappers, fields) {
  return Object.keys(payload).reduce((options, key) => {
    let sKey;
    if (Array.isArray(payload)) {
      sKey = +key;
    } else {
      sKey = JsonFinger.sanitizeKey(key);
    }

    options.push({ value: sKey, label: sKey });

    if (Array.isArray(payload[key])) {
      payload[key].forEach((item, i) => {
        i = Object.isFrozen(payload[key]) ? i : "";

        if (typeof item === "string") {
          if (i !== "") {
            options.push({
              value: `${sKey}[${i}]`,
              label: `${sKey}[${i}]`,
            });
          }
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

export function payloadToSchema(payload) {
  if (!payload) {
    return { type: "null" };
  }

  const type = Array.isArray(payload)
    ? "array"
    : typeof payload === "object"
      ? "object"
      : payload;

  switch (type) {
    case "array":
      return {
        type: "array",
        items: payload.map((item) => payloadToSchema(item)),
        additionalItems: !Object.isFrozen(payload),
      };
    case "object":
      return {
        type: "object",
        properties: Object.keys(payload).reduce((props, key) => {
          props[key] = payloadToSchema(payload[key]);
          return props;
        }, {}),
        additionalProperties: !Object.isFrozen(payload),
      };
    default:
      return { type };
  }
}

export function schemaToPayload(schema, pointer) {
  if (schema.type === "object") {
    pointer = JsonFinger.parse(pointer);

    const payload = Object.keys(schema.properties).reduce((payload, prop) => {
      payload[prop] = schemaToPayload(
        schema.properties[prop],
        JsonFinger.pointer(pointer.concat(prop))
      );

      return payload;
    }, {});

    if (schema.additionalProperties === false) {
      Object.freeze(payload);
    }

    return payload;
  } else if (schema.type === "array") {
    const schemaItems = Array.isArray(schema.items)
      ? schema.items
      : [schema.items];

    const payload = schemaItems.map((schema, i) => {
      return schemaToPayload(schema, `${pointer}[${i}]`);
    });

    if (schema.additionalItems === false) {
      Object.freeze(payload);
    }

    return payload;
  }

  return schema.type;
}

export function applyMappers(payload, mappers = []) {
  if (!Array.isArray(mappers) || !mappers.length) return payload;

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

export function payloadToFields(payload) {
  return Object.entries(payload).map(([name, value]) => {
    return {
      name,
      label: name,
      schema: payloadToSchema(value),
    };
  });
}

export function fieldsToPayload(fields) {
  if (cache.has(fields)) {
    return clonePayload(cache.get(fields));
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
  const options = payloadToOptions(payload, mappers, fields);
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
      const isFrozen = Object.isFrozen(from);
      value = JSON.parse(JSON.stringify(from));
      if (isFrozen) Object.freeze(value);
      return value;
    default:
      return cast;
  }
}
