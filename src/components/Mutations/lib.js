import {
  payloadToSchema,
  fieldsToPayload,
  applyMappers,
} from "../../lib/payload";

export function schemaToOptions(schema, name = "") {
  if (schema.type === "object") {
    const options = [
      {
        value: name,
        label: name,
      },
    ];

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

    return options.concat(
      schemaItems.reduce((options, item, i) => {
        if (schema.additionalItems) {
          i = "";
        }

        const pointer = `${name}[${i}]`;

        const isExpandable =
          /\[\]$/.test(pointer) && pointer.match(/\[\]/g).length >= 3;

        return options.concat(
          schemaToOptions(item, pointer).filter((opt) =>
            schema.additionalItems
              ? isExpandable || opt.value !== pointer
              : true
          )
        );
      }, [])
    );
  } else {
    return [{ label: name, value: name }];
  }
}

export function getFromOptions(fields, mappers) {
  const schema = payloadToSchema(
    applyMappers(fieldsToPayload(fields), mappers)
  );

  return schemaToOptions(schema);
}
