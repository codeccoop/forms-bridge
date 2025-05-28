import {
  payloadToSchema,
  fieldsToPayload,
  applyMappers,
} from "../../lib/payload";

export function schemaToOptions(schema, name = "") {
  const isExpansible = name.match(/\[\](?=[^\[])/g)?.length >= 2;

  if (schema.type === "object") {
    const options = [
      {
        value: name,
        label: name,
      },
    ];

    if (isExpansible) {
      options.push({ value: name + "[]", label: name + "[]" });
    }

    return options.concat(
      Object.keys(schema.properties).reduce((options, prop) => {
        const pointer = name ? `${name}.${prop}` : prop;
        return options.concat(
          schemaToOptions(schema.properties[prop], pointer)
        );
      }, [])
    );
  } else if (schema.type === "array") {
    const options = [{ value: name, label: name }];

    const schemaItems = Array.isArray(schema.items)
      ? schema.items
      : [schema.items];

    const isTuple = schemaItems.reduce((isTuple, item) => {
      return isTuple || item.type !== schemaItems[0]?.type;
    }, false);

    return options
      .concat(
        schemaItems.reduce((options, item) => {
          if (isTuple) return options;

          const pointer = `${name}[]`;

          return options.concat(
            schemaToOptions(item, pointer).filter(
              (opt) => isExpansible || opt.value !== pointer
            )
          );
        }, [])
      )
      .concat(
        schemaItems.reduce((options, item, i) => {
          if (schema.additionalItems) return options;
          const pointer = `${name}[${i}]`;

          return options.concat(schemaToOptions(item, pointer));
        }, [])
      );
  } else {
    const options = [{ label: name, value: name }];
    if (isExpansible) {
      options.push({ label: name + "[]", value: name + "[]" });
    }

    return options;
  }
}

export function getFromOptions(fields, mappers) {
  const schema = payloadToSchema(
    applyMappers(fieldsToPayload(fields), mappers)
  );

  return schemaToOptions(schema);
}
