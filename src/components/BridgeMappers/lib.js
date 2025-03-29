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
      const key = keys[i] === -1 ? 0 : keys[i];
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

function payloadToOptions(payload, fields) {
  return Object.keys(payload).reduce((options, key) => {
    let sKey;
    if (Array.isArray(payload)) {
      sKey = +key;
    } else {
      sKey = JsonFinger.sanitizeKey(key);
    }

    options.push({ value: sKey, label: sKey });

    if (Array.isArray(payload[key])) {
      // options.push({
      //   value: sKey + "[]",
      //   label: sKey + "[]",
      // });

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
      JsonFinger.validate(mutation.from, "get") &&
      JsonFinger.validate(mutation.to, "set");

    if (!isValid) {
      continue;
    }

    const isset = finger.isset(mutation.from);
    if (!isset) {
      continue;
    }

    const expansion = [];
    const value = finger.get(mutation.from, expansion);

    if (mutation.from.includes("[]")) {
      for (const pointer of expansion) {
        let item = value;
        const matches = pointer.matchAll(/\[(\d+)\]/g);
        for (let i = 0; i < matches.length; i++) {
          const index = +matches[i][1];
          item = item[index];

          if (item === undefined) {
            break;
          }
        }

        if (item === undefined) {
          continue;
        }

        if (mutation.cast !== "copy") {
          finger.unset(pointer);
        }

        if (mutation.cast !== "null") {
          if (mutation.cast === "copy" || mutation.cast === "inherit") {
            finger.set(mutation.to, value);
          } else {
            finger.set(mutation.to, {});
          }
        }
      }
    } else {
      if (mutation.cast !== "copy") {
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
  }

  const mutatedOptions = payloadToOptions(finger.getData()); /*.reduce(
    (options, opt) => {
      const upstream = { ...opt };
      const upstreamKeys = JsonFinger.parse(upstream.value);
      upstream.value = upstreamKeys[0];

      mutations.reverse().forEach((mutation) => {
        if (mutation.to === upstream.value) {
          upstream.value = mutation.from;
        }
      });

      const fromField = fields.find((field) => {
        const fieldKey = JsonFinger.sanitizeKey(field.name);
        return fieldKey === upstream.value;
      });

      console.log({ opt, fromField });
      if (
        fromField &&
        fromField.schema.type === "array" &&
        fromField.schema.additionalItems
      ) {
        const unindexed = opt.value.replace(/\[\d+\]/g, "[]");
        opt.value = unindexed;
        opt.label = unindexed;

        if (!options.find((opt) => opt.value === unindexed)) {
          options.push(opt);
        }
      } else {
        options.push(opt);
      }

      return options;
    },
    []
  ); */

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

      // fields.push({
      //   label: `${name}[]`,
      //   value: `${name}[]`,
      // });

      if (schema.maxItems || Array.isArray(schema.items)) {
        const items = schema.maxItems || schema.items.length;
        for (let i = 0; i < items; i++) {
          fields.push({
            label: `${name}[${i}]`,
            value: `${name}[${i}]`,
          });
        }
      } /* else if (schema.additionalItems) {
        fieldsToOptions(
          [
            {
              name: `${name}[]`,
              schema: schema.items,
            },
          ],
          options
        );
      } */
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
