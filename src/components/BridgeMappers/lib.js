import JsonFinger from "./JsonFinger";

const cache = new WeakMap();

function optionsToPayload(options) {
  if (cache.has(options)) {
    return cache.get(options);
  }

  const payload = {};

  for (const opt of options) {
    const keys = JsonFinger.parse(opt.value);

    let partial = payload;

    for (let i = 0; i < keys.length; i++) {
      const key = keys[i];
      const nextKey = keys[i + 1] === undefined ? "no-key" : keys[i + 1];

      if (+nextKey === nextKey) {
        partial[key] = Array.isArray(partial[key]) ? partial[key] : [];
      } else {
        partial[key] = partial[key] ? partial[key] : {};
      }

      partial = partial[key];
    }
  }

  cache.set(options, payload);
  return payload;
}

function payloadToOptions(payload) {
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
        if (Object.keys(item).length === 0) {
          options.push({
            value: `${sKey}[${i}]`,
            label: `${sKey}[${i}]`,
          });
        } else {
          options = options.concat(
            payloadToOptions(item).map((opt) => {
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
    } else if (payload[key]) {
      options = options.concat(
        payloadToOptions(payload[key]).map((opt) => {
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

export function getFromOptions(fields, mappers, index) {
  const options = fieldsToOptions(fields);

  const mutations = mappers.slice(0, index);
  const payload = optionsToPayload(options);
  const finger = new JsonFinger(payload);

  for (const mutation of mutations) {
    const isValid =
      JsonFinger.validate(mutation.from) && JsonFinger.validate(mutation.to);

    if (!isValid) {
      continue;
    }

    const isset = finger.isset(mutation.from);
    if (!isset) {
      continue;
    }

    const value = finger.get(mutation.from);

    if (mutation.cast !== "copy" && mutation.from !== mutation.to) {
      finger.unset(mutation.from);
    }

    if (mutation.cast !== "null") {
      if (mutation.cast === "copy" || mutation.cast === "inherit") {
        finger.set(mutation.to, JSON.parse(JSON.stringify(value)));
      } else {
        finger.set(mutation.to, {});
      }
    }
  }

  const mutatedOptions = payloadToOptions(finger.getData());
  return [{ label: "", value: "" }].concat(mutatedOptions);
}

function fieldsToOptions(fields, options = []) {
  if (cache.has(fields)) {
    return cache.get(fields);
  }

  options = fields.reduce((fields, { name, schema }) => {
    name = name.replace(/\./g, "\.");
    name = name.replace(/\[/g, "\[");
    name = name.replace(/\]/g, "\]");
    name = JsonFinger.sanitizeKey(name);

    if (schema.type === "array") {
      fields.push({
        label: name,
        value: name,
      });

      if (!schema.additionalItems) {
        if (schema.maxItems || Array.isArray(schema.items)) {
          const items = schema.maxItems || schema.items.length;
          for (let i = 0; i < items; i++) {
            fields.push({
              label: `${name}[${i}]`,
              value: `${name}[${i}]`,
            });
          }
        }
      }
    } else if (schema.type === "object") {
      fields.push({
        label: name,
        value: name,
      });

      const subFields = Object.keys(schema.properties).map((prop) => {
        let attr = JsonFinger.sanitizeKey(prop);
        if (attr[0] !== "[") {
          attr = "." + attr;
        }

        return {
          name: `${name}${attr}`,
          schema: schema.properties[prop],
        };
      });

      fieldsToOptions(subFields, options);
    } else {
      fields.push({
        label: name,
        value: name,
      });
    }

    return fields;
  }, options);

  cache.set(fields, options);
  return options;
}
