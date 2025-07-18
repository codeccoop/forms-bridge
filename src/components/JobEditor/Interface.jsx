import FieldWrapper from "../FieldWrapper";
import Toggle from "../Toggle";
import { mutateSchema } from "./lib";

const { useEffect, useRef } = wp.element;
const { TextControl, SelectControl, Button } = wp.components;
const { __ } = wp.i18n;

const TYPE_OPTIONS = [
  {
    value: "string",
    label: "string",
  },
  {
    value: "integer",
    label: "integer",
  },
  {
    value: "number",
    label: "number",
  },
  {
    value: "boolean",
    label: "boolean",
  },
  {
    value: "object",
    label: "object",
  },
  {
    value: "array",
    label: "array",
  },
];

const DEFAULT_SCHEMA = { type: "string" };
const DEFAULT_FIELD = { name: "", schema: DEFAULT_SCHEMA };

export default function JobInterfaceEditor({ fields, setFields }) {
  const update = (index, field) => {
    const newFields = fields
      .slice(0, index)
      .concat([field])
      .concat(fields.slice(index + 1, fields.length));

    setFields(newFields);
  };

  const add = (index, field = DEFAULT_FIELD) => {
    const newFields = fields
      .slice(0, index)
      .concat([field])
      .concat(fields.slice(index, fields.length));

    setFields(newFields);
  };

  const remove = (index) => {
    const newFields = fields
      .slice(0, index)
      .concat(fields.slice(index + 1, fields.length));

    setFields(newFields);
  };

  useEffect(() => {
    if (!fields.length) {
      add(0, { ...DEFAULT_FIELD });
    }
  }, [fields]);

  return (
    <>
      <TableHeaders
        style={{ marginLeft: "calc(80px + 1rem)", marginBottom: "-1rem" }}
      />
      <div style={{ display: "flex", flexDirection: "column" }}>
        {fields.map((field, i) => (
          <InterfaceFieldEditor
            key={i}
            field={field}
            update={(field) => update(i, field)}
            remove={() => remove(i)}
            add={() => add(i + 1, { ...DEFAULT_FIELD })}
          />
        ))}
      </div>
    </>
  );
}

function TableHeaders({ style = {} }) {
  return (
    <div
      style={{
        display: "flex",
        gap: "0.5rem",
        paddingBottom: "0.5rem",
        ...style,
      }}
    >
      <div style={{ width: "clamp(200px, 15vw, 300px)", padding: "0 5px" }}>
        <b>{__("Name", "forms-bridge")}</b>
      </div>
      <div style={{ width: "clamp(200px, 15vw, 300px)", padding: "0 5px" }}>
        <b>{__("Type", "forms-bridge")}</b>
      </div>
      <div style={{ width: "clamp(200px, 15vw, 300px)", padding: "0 5px" }}>
        <b>{__("Required", "forms-bridge")}</b>
      </div>
    </div>
  );
}

function InterfaceFieldEditor({ field, update, remove, add }) {
  return (
    <>
      <div
        style={{
          display: "flex",
          gap: "0.5rem",
          padding: "1rem 0",
          borderBottom: "1px solid rgba(0, 0, 0, 0.1)",
        }}
      >
        <Button
          size="compact"
          variant="secondary"
          onClick={add}
          style={{
            width: "40px",
            height: "40px",
            justifyContent: "center",
          }}
          __next40pxDefaultSize
        >
          +
        </Button>
        <Button
          size="compact"
          variant="secondary"
          isDestructive
          onClick={remove}
          style={{
            width: "40px",
            height: "40px",
            justifyContent: "center",
          }}
          __next40pxDefaultSize
        >
          -
        </Button>
        <FieldWrapper>
          <TextControl
            value={field.name}
            onChange={(name) => update({ ...field, name })}
            __nextHasNoMarginBottom
            __next40pxDefaultSize
          />
        </FieldWrapper>
        <FieldWrapper>
          <SelectControl
            value={field.schema.type}
            onChange={(type) => {
              schema = mutateSchema(type, field.schema);
              update({ ...field, schema });
            }}
            options={TYPE_OPTIONS}
            __nextHasNoMarginBottom
            __next40pxDefaultSize
          />
        </FieldWrapper>
        <div
          style={{
            paddingLeft: "0.5rem",
            display: "flex",
            justifyContent: "center",
            alignItems: "center",
          }}
        >
          <Toggle
            checked={field.required}
            onChange={() => update({ ...field, required: !field.required })}
          />
        </div>
      </div>
      {(field.schema.type === "object" && (
        <ObjectSchemaEditor
          schema={field.schema}
          setSchema={(schema) => update({ ...field, schema })}
        />
      )) ||
        null}
      {(field.schema.type === "array" && (
        <ArraySchemaEditor
          schema={field.schema}
          setSchema={(schema) => update({ ...field, schema })}
        />
      )) ||
        null}
    </>
  );
}

function SchemaEditor({
  name,
  setName,
  schema,
  setSchema,
  required,
  toggleRequired,
  remove,
}) {
  return (
    <>
      <div style={{ display: "flex", gap: "0.5rem" }}>
        <Button
          size="compact"
          variant="secondary"
          isDestructive
          disabled={!remove}
          onClick={remove}
          style={{
            width: "40px",
            height: "40px",
            justifyContent: "center",
          }}
          __next40pxDefaultSize
        >
          -
        </Button>
        <FieldWrapper>
          <TextControl
            disabled={!setName}
            value={name}
            onChange={setName}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
        </FieldWrapper>
        <FieldWrapper>
          <SelectControl
            value={schema.type}
            onChange={(type) => setSchema(mutateSchema(type, schema))}
            options={TYPE_OPTIONS}
            __nextHasNoMarginBottom
            __next40pxDefaultSize
          />
        </FieldWrapper>

        <div
          style={{
            paddingLeft: "0.5rem",
            display: "flex",
            justifyContent: "center",
            alignItems: "center",
          }}
        >
          <Toggle
            disabled={!toggleRequired}
            checked={required}
            onChange={toggleRequired}
          />
        </div>
      </div>
      {(schema.type === "object" && (
        <ObjectSchemaEditor
          schema={schema}
          setSchema={(schemaPatch) => setSchema({ ...schema, ...schemaPatch })}
        />
      )) ||
        null}
      {(schema.type === "array" && (
        <ArraySchemaEditor
          schema={schema}
          setSchema={(schemaPatch) => setSchema({ ...schema, ...schemaPatch })}
        />
      )) ||
        null}
    </>
  );
}

function ObjectSchemaEditor({ schema, setSchema }) {
  if (!schema.properties) {
    schema.properties = {};
  }

  const orderRef = useRef(Object.keys(schema.properties));

  return (
    <div
      style={{
        marginLeft: "0.5rem",
        borderLeft: "1px dashed",
        padding: "0.5rem 0 0.5rem 40px",
        background: "rgb(245, 245, 245)",
      }}
    >
      <TableHeaders style={{ marginLeft: "calc(40px + 0.5rem)" }} />
      <div style={{ display: "flex", flexDirection: "column", gap: "0.5rem" }}>
        {orderRef.current.map((prop, i) => {
          if (!schema.properties[prop]) return null;

          return (
            <SchemaEditor
              key={i}
              name={prop}
              setName={(newName) => {
                newName = newName.trim();
                if (schema.properties[newName]) return;

                const properties = { ...schema.properties };
                properties[newName] = schema.properties[prop];
                delete properties[prop];

                setSchema({
                  ...schema,
                  properties,
                  additionalProperties:
                    Object.keys(schema.properties).length === 0,
                });

                orderRef.current[i] = newName;
              }}
              required={schema.required?.includes(prop)}
              toggleRequired={() => {
                const required = Array.isArray(schema.required)
                  ? [...schema.required]
                  : [];

                const index = required.findIndex(
                  (required) => prop === required
                );
                if (index !== -1) {
                  required.splice(index, 1);
                } else {
                  required.push(prop);
                }

                setSchema({ ...schema, required });
              }}
              schema={schema.properties[prop]}
              setSchema={(propSchema) => {
                setSchema({
                  ...schema,
                  properties: {
                    ...schema.properties,
                    [prop]: propSchema,
                  },
                });
              }}
              remove={() => {
                const properties = { ...schema.properties };
                delete properties[prop];
                setSchema({ ...schema, properties });
                orderRef.current.splice(i, 1);
              }}
            />
          );
        })}
      </div>
      <Button
        size="compact"
        variant="secondary"
        disabled={Object.prototype.hasOwnProperty.call(schema.properties, "")}
        onClick={() => {
          setSchema({
            ...schema,
            properties: { ...schema.properties, "": { type: "string" } },
          });
          orderRef.current.push("");
        }}
        style={{
          marginTop: "0.5rem",
          width: "40px",
          height: "40px",
          justifyContent: "center",
        }}
        __next40pxDefaultSize
      >
        +
      </Button>
    </div>
  );
}

function ArraySchemaEditor({ schema, setSchema }) {
  const items = Array.isArray(schema.items) ? schema.items : [schema.items];

  return (
    <div
      style={{
        marginLeft: "0.5rem",
        borderLeft: "1px dashed",
        padding: "0.5rem 0 0.5rem 40px",
        background: "rgb(245, 245, 245)",
      }}
    >
      <TableHeaders style={{ marginLeft: "calc(40px + 0.5rem)" }} />
      <div style={{ display: "flex", flexDirection: "column", gap: "0.5rem" }}>
        {items.map((item, i) => (
          <SchemaEditor
            key={i}
            name={Array.isArray(schema.items) ? i : "{index}"}
            schema={item}
            setSchema={(itemSchema) => {
              if (Array.isArray(schema.items)) {
                const items = [...schema.items];
                items[i] = itemSchema;
                setSchema({ ...schema, items, additionalItems: false });
              } else {
                setSchema({
                  ...schema,
                  items: itemSchema,
                  additionalItems: true,
                });
              }
            }}
            required={Array.isArray(schema.items)}
            toggleRequired={null}
            remove={
              !Array.isArray(schema.items)
                ? null
                : () => {
                    const newSchema = {
                      ...schema,
                      items: items
                        .slice(0, i)
                        .concat(items.slice(i + 1, items.length)),
                    };

                    if (newSchema.items.length === 1) {
                      newSchema.items = newSchema.items[0];
                    }

                    setSchema(newSchema);
                  }
            }
          />
        ))}
      </div>
      <Button
        size="compact"
        variant="secondary"
        onClick={() => {
          setSchema({
            ...schema,
            items: Array.isArray(schema.items)
              ? [...schema.items, DEFAULT_SCHEMA]
              : [schema.items, DEFAULT_SCHEMA],
          });
        }}
        style={{
          marginTop: "0.5rem",
          width: "40px",
          height: "40px",
          justifyContent: "center",
        }}
        __next40pxDefaultSize
      >
        +
      </Button>
    </div>
  );
}
