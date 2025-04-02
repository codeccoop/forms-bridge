import JsonFinger from "./../../lib/JsonFinger";
import { fieldsToPayload, applyMappers } from "../../lib/payload";

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

export function getFromOptions(fields, mappers) {
  const payload = applyMappers(fieldsToPayload(fields), mappers);
  const options = payloadToOptions(payload, mappers, fields);
  return [{ label: "", value: "" }].concat(options);
}
