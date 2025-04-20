import { useTemplateConfig } from "../../../providers/Templates";
import TemplateStep from "./Step";
import Field from "../Field";
import { prependEmptyOption, sortByNamesOrder } from "../../../lib/utils";

const { useMemo, useState, useEffect, useRef } = wp.element;
const { SelectControl } = wp.components;
const { __ } = wp.i18n;

const FIELDS_ORDER = ["name"];

function validateCredential(credential, schema, fields) {
  const isValid = fields.reduce((isValid, { name }) => {
    return isValid && !!credential[name];
  }, true);

  if (!isValid) return isValid;

  return Object.keys(schema).reduce((isValid, name) => {
    return isValid && !!credential[name];
  }, isValid);
}

export default function CredentialStep({ credentials, fields, data, setData }) {
  const names = useMemo(
    () => new Set(credentials.map(({ name }) => name)),
    [credentials]
  );

  const { credential: schema = {} } = useTemplateConfig();

  const validCredentials = useMemo(() => {
    return credentials.filter((credential) =>
      validateCredential(credential, schema, fields)
    );
  }, [credentials]);

  const credentialOptions = useMemo(() => {
    return prependEmptyOption(
      validCredentials.map(({ name }) => ({ label: name, value: name }))
    );
  }, [validCredentials]);

  const previousReuse = useRef(data.name || "");
  const [reuse, setReuse] = useState(data.name || "");

  const [name, setName] = useState(data.name || "");
  const nameConflict = useMemo(
    () => data.name !== name.trim() && names.has(name.trim()),
    [names, name]
  );

  useEffect(() => {
    if (reuse !== previousReuse.current) {
      setName("");
      setData();
    } else if (!reuse && !nameConflict && data.name !== name) {
      setData({ name });
    }

    previousReuse.current = reuse;
  }, [data.name, reuse, name, nameConflict]);

  const credential = useMemo(
    () => validCredentials.find(({ name }) => name === reuse),
    [reuse, validCredentials]
  );

  useEffect(() => {
    if (!credential || reuse !== previousReuse.current) return;
    setData({ ...credential });
  }, [credential]);

  const sortedFields = useMemo(
    () => sortByNamesOrder(fields, FIELDS_ORDER),
    [fields]
  );

  const filteredFields = useMemo(() => {
    if (credential) return [];
    return sortedFields.slice(1);
  }, [sortedFields, credential]);

  const nameField = useMemo(() => sortedFields[0], [sortedFields]);

  return (
    <TemplateStep
      name={__("Credential", "forms-bridge")}
      description={__("Configure the backend credentials", "forms-bridge")}
    >
      {credentialOptions.length > 0 && (
        <SelectControl
          label={__("Reuse an existing credential", "forms-bridge")}
          value={reuse}
          options={credentialOptions}
          onChange={setReuse}
          __nextHasNoMarginBottom
        />
      )}
      {!reuse && (
        <Field
          data={{
            ...nameField,
            value: name,
            onChange: setName,
          }}
          error={
            nameConflict
              ? __("This name is already in use", "forms-bridge")
              : false
          }
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
