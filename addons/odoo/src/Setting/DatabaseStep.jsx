import useOdooApi from "../hooks/useOdooApi";
import TemplateStep from "../../../../src/components/Templates/Steps/Step";
import Field from "../../../../src/components/Templates/Field";

const { SelectControl } = wp.components;
const { useMemo, useState, useEffect } = wp.element;
const { __ } = wp.i18n;

const fieldsOrder = ["name", "backend", "username", "password"];

export default function DatabaseStep({ fields, data, setData }) {
  const [{ databases }] = useOdooApi();
  const databaseNames = useMemo(
    () => new Set(databases.map(({ name }) => name)),
    [databases]
  );
  const [databaseName, setDatabaseName] = useState(
    databaseNames.has(data.name) ? data.name : ""
  );
  const [newName, setNewName] = useState(data.name || "");

  const databaseOptions = [{ label: "", value: "" }].concat(
    databases.map(({ name }) => ({ label: name, value: name }))
  );

  const database = useMemo(
    () => databases.find(({ name }) => name === databaseName),
    [databases, databaseName]
  );

  useEffect(() => {
    if (database) {
      setData({ ...database });
    } else {
      setData({
        name: null,
        backend: null,
        username: null,
        password: null,
      });
    }
  }, [database]);

  const sortedFields = useMemo(
    () =>
      fields.sort((a, b) => {
        if (!fieldsOrder.includes(a.name)) {
          return 1;
        } else if (!fieldsOrder.includes(b.name)) {
          return -1;
        } else {
          return fieldsOrder.indexOf(a.name) - fieldsOrder.indexOf(b.name);
        }
      }),
    [fields]
  );

  const filteredFields = useMemo(
    () =>
      databaseName ? [] : sortedFields.filter(({ name }) => name !== "name"),
    [databaseName, sortedFields]
  );

  const nameField = useMemo(
    () => sortedFields.find(({ name }) => name === "name"),
    [sortedFields]
  );

  const nameConflict = useMemo(
    () => (newName && databaseNames.has(newName.trim())) || false,
    [databaseNames, newName]
  );

  useEffect(() => {
    if (!nameConflict && newName) {
      setData({ name: newName });
    }
  }, [newName]);

  useEffect(() => {
    if (databaseName) {
      setNewName("");
    }
  }, [databaseName]);

  return (
    <TemplateStep
      name={__("Database", "forms-bridge")}
      description={__("Configure the Odoo database connection", "forms-bridge")}
    >
      <SelectControl
        label={__("Reuse database", "forms-bridge")}
        value={databaseName}
        options={databaseOptions}
        onChange={(value) => setDatabaseName(value)}
        __nextHasNoMarginBottom
      />
      {!databaseName && (
        <Field
          error={
            nameConflict
              ? __("This name is already in use", "forms-bridge")
              : false
          }
          data={{
            ...nameField,
            value: newName || "",
            onChange: setNewName,
          }}
        />
      )}
      {filteredFields.map((field) => (
        <Field
          data={{
            ...field,
            value: data[field.name] || "",
            onChange: (value) => setData({ [field.name]: value }),
          }}
        />
      ))}
    </TemplateStep>
  );
}
