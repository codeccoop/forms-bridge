import useZohoApi from "../hooks/useZohoApi";
import TemplateStep from "../../../../src/components/Templates/Steps/Step";
import Field from "../../../../src/components/Templates/Field";

const { SelectControl } = wp.components;
const { useMemo, useState, useEffect } = wp.element;
const { __ } = wp.i18n;

const fieldsOrder = [
  "name",
  "backend",
  "organization_id",
  "client_id",
  "client_secret",
];

export default function CredentialStep({ fields, data, setData }) {
  const [{ credentials }] = useZohoApi();
  const credentialNames = useMemo(
    () =>
      new Set(
        credentials.filter(({ backend }) => backend).map(({ name }) => name)
      ),
    [credentials]
  );

  const [name, setName] = useState(
    credentialNames.has(data.name) ? data.name : ""
  );
  const [newName, setNewName] = useState(data.name || "");

  const credentialOptions = [{ label: "", value: "" }].concat(
    Array.from(credentialNames).map((name) => ({ label: name, value: name }))
  );

  const credential = useMemo(
    () => credentials.find((credential) => credential.name === name),
    [credentials, name]
  );

  useEffect(() => {
    if (credential) {
      setData({ ...credential });
    } else {
      setData({
        name: null,
        backend: null,
        organization_id: null,
        client_id: null,
        client_secret: null,
      });
    }
  }, [credential]);

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
    () => (name ? [] : sortedFields.filter(({ name }) => name !== "name")),
    [name, sortedFields]
  );

  const nameField = useMemo(
    () => sortedFields.find(({ name }) => name === "name"),
    [sortedFields]
  );

  const nameConflict = useMemo(
    () => (newName && credentialNames.has(newName.trim())) || false,
    [credentialNames, newName]
  );

  useEffect(() => {
    if (!nameConflict && newName) {
      setData({ name: newName });
    }
  }, [newName]);

  useEffect(() => {
    if (name) {
      setNewName("");
    }
  }, [name]);

  return (
    <TemplateStep
      name={__("Credentials", "forms-bridge")}
      description={__("Configure the Zoho oauth credentials", "forms-bridge")}
    >
      <SelectControl
        label={__("Reuse a credential", "forms-bridge")}
        value={name}
        options={credentialOptions}
        onChange={(value) => setName(value)}
        __nextHasNoMarginBottom
      />
      {!name && (
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
