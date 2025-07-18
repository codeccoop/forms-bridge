const { __ } = wp.i18n;

function pruneEmptySchemas(schema) {
  if (schema.type === "object") {
    delete schema.properties[""];

    Object.keys(schema.properties).forEach((prop) => {
      schema.properties[prop] = pruneEmptySchemas(schema.properties[prop]);
    });

    schema.additionalProperties = Object.keys(schema.properties).length === 0;
    return schema;
  } else if (schema.type === "array") {
    if (Array.isArray(schema.items)) {
      schema.items = schema.map.filter((item) => pruneEmptySchemas(item));

      if (schema.items.length === 1) {
        schema.items = schema.items[0];
      }
    } else {
      schema.items = pruneEmptySchemas(schema.items);
    }

    schema.additionalItems = !Array.isArray(schema.items);
    return schema;
  }

  return schema;
}

export function pruneEmptyFileds(fields) {
  return fields
    .filter((field) => field.name)
    .map((field) => {
      field.schema = pruneEmptySchemas(field.schema);
      return field;
    });
}

export function mutateSchema(type, fromSchema) {
  if (fromSchema.type === "array") {
    if (Array.isArray(fromSchema.items)) {
      fromSchema = { type: "string" };
    } else {
      fromSchema = fromSchema.items;
    }
  } else if (fromSchema.type === "object") {
    const props = Object.keys(fromSchema.properties);
    if (props.length === 1) {
      fromSchema = fromSchema.properties[props[0]];
    } else {
      fromSchema = { type: "string" };
    }
  }

  let newSchema = { type };
  if (type === "array") {
    newSchema.items = fromSchema;
    newSchema.additionalItems = true;
  } else if (type === "object") {
    newSchema.properties = { "": { type: fromSchema.type } };
    newSchema.required = [];
    newSchema.additionalProperties = false;
  }

  console.log(newSchema);
  return newSchema;
}

export function jobTemplate(addon) {
  return {
    addon,
    id: `${addon}-new-job`,
    name: "new-job",
    title: __("New job", "forms-bridge"),
    description: "",
    input: [],
    output: [],
    snippet: "",
    method: `forms_bridge_${addon}_new_job`,
  };
}
