import JsonFinger from "./JsonFinger";

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
        additionalItems: Object.isFrozen(payload),
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
    const keys = JsonFinger.parse(pointer);

    const payload = Object.keys(schema.properties).reduce((payload, prop) => {
      payload[prop] = schemaToPayload(
        schema.properties[prop],
        JsonFinger.pointer(keys.concat(prop))
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

    if (schema.additionalItems === true) {
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

    if (!isValid) continue;

    let value;
    if (!finger.isset(mapper.from)) {
      if (JsonFinger.isConditional(mapper.from)) {
        continue;
      }

      value = { type: "null" };
    } else {
      value = finger.get(mapper.from);
    }

    if (
      (mapper.cast !== "copy" && mapper.from !== mapper.to) ||
      mapper.cast === "null"
    ) {
      finger.unset(mapper.from);
    }

    if (mapper.cast !== "null") {
      finger.set(mapper.to, castValue(value, mapper));
    }
  }

  return finger.data;
}

export function payloadToFields(payload) {
  return Object.entries(payload).map(([name, value]) => {
    const schema = payloadToSchema(value);
    return {
      name,
      label: name,
      schema,
    };
  });
}

export function fieldsToPayload(fields) {
  const finger = new JsonFinger({});

  fields.forEach(({ name, schema }) => {
    const nameKeys = JsonFinger.parse(name);
    const pointer = JsonFinger.pointer(nameKeys);
    finger.set(pointer, schemaToPayload(schema, pointer));
  });

  return finger.data;
}

export function castValue(value, mapper) {
  if (mapper.from.indexOf("[]") !== -1) {
    return castExpandedValue(value, mapper);
  }

  switch (mapper.cast) {
    case "join":
    case "json":
    case "concat":
    case "csv":
      return "string";
    case "count":
      return "integer";
    case "sum":
      return "number";
    case "copy":
    case "inherit":
      return value;
    case "not":
    case "and":
    case "or":
    case "xor":
      return "boolean";
    default:
      return mapper.cast;
  }
}

function castExpandedValue(values, mapper) {
  if (!Array.isArray(values)) return [];

  const isFrozen = Object.isFrozen(values);
  if (isFrozen) values = [...values];

  const isExpanded = mapper.from.replace(/\[\]$/, "").indexOf("[]") !== -1;

  if (!isExpanded) {
    return values.map((value) => {
      return castValue(value, { cast: mapper.cast, from: "", to: "" });
    });
  }

  // const toExpansions = mapper.to.replace(/\[\]$/, "").match(/\[\]/g) || [];
  // const fromExpansions = mapper.from.replace(/\[\]$/, "").match(/\[\]/g) || [];

  // if (!fromExpansions.length && toExpansions > 1) {
  //   return [];
  // } else if (
  //   fromExpansions.length &&
  //   toExpansions.length > fromExpansions.length
  // ) {
  //   return [];
  // }

  const parts = mapper.from.split("[]").filter((p) => p);
  const before = parts[0];
  const after = parts.slice(1).join("[]");

  for (let i = 0; i < values.length; i++) {
    const pointer = `${before}[${i}]${after}`;
    values[i] = castValue(values[i], {
      from: pointer,
      to: "",
      cast: mapper.cast,
    });
  }

  if (isFrozen) return Object.freeze(values);
  return values;
}

const TYPES_COMPATIBILITY = {
  boolean: ["integer", "null"],
  integer: ["number", "boolean", "null"],
  number: ["integer", "boolean", "null"],
  string: ["integer", "number", "boolean", "null"],
  null: ["integer", "number", "boolean", "string", "array", "object"],
  array: ["integer", "number", "boolean", "string", "null"],
  object: [],
};

export function checkType(from, to, strict = true) {
  if (!from || !to) {
    return false;
  }

  if (from.type !== to.type) {
    if (strict) {
      return false;
    } else {
      if (!TYPES_COMPATIBILITY[to.type]?.includes(from.type)) {
        return false;
      } else if (to.type === "array" && from.type !== "array") {
        from = {
          type: "array",
          items: [{ type: from.type }],
        };

        if (from.items.type === "null") {
          from.items = [];
        }
      } else {
        return to.type;
      }
    }
  }

  let result;
  if (from.type === "object") {
    const fromKeys = Object.keys(from.properties || {});
    const toKeys = Object.keys(to.properties || {});

    if (fromKeys > toKeys && to.additionalProperties === false) {
      return false;
    }

    if (to.additionalProperties) {
      return true;
    }

    result = fromKeys.reduce((typeCheck, key) => {
      if (!typeCheck) return typeCheck;
      if (!toKeys.includes(key)) return false;

      return typeCheck && checkType(from[key], to[key], strict);
    }, true);
  } else if (from.type === "array") {
    if (Array.isArray(from.items)) {
      if (Array.isArray(to.items)) {
        if (to.maxItems && to.maxItems < from.items.length) {
          return false;
        }

        result = from.items.reduce((typeCheck, item, i) => {
          if (!typeCheck) return typeCheck;
          return typeCheck && checkType(item, to.items[i]);
        }, true);
      } else {
        if (from.maxItems && to.items.length > from.maxItems) {
          return false;
        }

        result = from.items.reduce((typeCheck, item) => {
          if (!typeCheck) return typeCheck;
          return typeCheck && checkType(item, from.items, strict);
        }, true);
      }
    } else if (Array.isArray(to.items)) {
      result = to.items.reduce((typeCheck, item) => {
        if (!typeCheck) return typeCheck;
        return typeCheck && checkType(from.items, item, strict);
      }, true);
    } else {
      result = checkType(from.items, to.items, strict);
    }
  }

  return result;
}
