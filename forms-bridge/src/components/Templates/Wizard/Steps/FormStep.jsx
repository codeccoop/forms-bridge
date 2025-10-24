import TemplateStep from "./Step";
import Field from "../../Field";
import { sortByNamesOrder, prependEmptyOption } from "../../../../lib/utils";
import { useForms } from "../../../../providers/Forms";
import { useTemplateConfig } from "../../../../providers/Templates";

const { useMemo, useState, useEffect, useRef } = wp.element;
const { __ } = wp.i18n;
const { SelectControl } = wp.components;

const fieldsOrder = ["title"];

function validateForm(form, schema, fields, integration) {
  if (form._id.indexOf(integration) !== 0) {
    return false;
  }

  let isValid = schema.fields.reduce((isValid, { name, label, type }) => {
    if (!isValid) return isValid;

    const pair =
      form.fields.find((field) => name === field.name) ||
      (integration === "wpforms" &&
        form.fields.find((field) => label === field.name));

    if (!pair) return false;

    return isValid && pair.type === type;
  }, true);

  return (
    isValid &&
    fields
      .filter((field) => field.ref === "#form/fields[]")
      .reduce((isValid, { name, required, type }) => {
        if (!isValid) return isValid;

        const pair = form.fields.find((field) => field.name === name);

        if (!pair) {
          if (required) return false;
          return isValid;
        } else {
          return isValid && type === pair.type;
        }
      }, true)
  );
}

export default function FormStep({ fields, data, setData, integration }) {
  const [{ form: schema }] = useTemplateConfig();
  const [forms] = useForms();

  const validForms = useMemo(
    () =>
      forms.filter((form) => validateForm(form, schema, fields, integration)),
    [forms, integration]
  );

  const formOptions = useMemo(() => {
    return prependEmptyOption(
      validForms.map(({ id, title }) => ({
        label: title,
        value: id,
      }))
    );
  }, [validForms]);

  const [formId, setFormId] = useState("");
  const previousFormId = useRef("");

  useEffect(() => {
    setFormId("");
  }, [integration]);

  useEffect(() => {
    if (formId !== previousFormId.current) {
      setData();
    }

    return () => {
      previousFormId.current = formId;
    };
  }, [formId]);

  const form = useMemo(
    () => forms.find(({ id }) => id == formId),
    [forms, formId]
  );

  useEffect(() => {
    if (!form) return;

    const data = { id: form.id };
    schema.fields.forEach((schema) => {
      switch (schema.type) {
        case "object":
          data[schema.name] = [];
          break;
        case "boolean":
          data[schema.name] = false;
          break;
        default:
          data[schema.name] = "";
      }
    });

    setData(data);
  }, [form]);

  const sortedFields = useMemo(
    () => sortByNamesOrder(fields, fieldsOrder),
    [fields]
  );

  const filteredFields = useMemo(() => {
    if (form) return [];
    return sortedFields.filter((field) => field.name !== "id");
  }, [form, sortedFields]);

  return (
    <TemplateStep
      name={__("Form", "forms-bridge")}
      description={__("Populate the form default values", "forms-bridge")}
    >
      {formOptions.length > 0 && (
        <SelectControl
          label={__("Reuse an existing form", "forms-bridge")}
          value={formId}
          options={formOptions}
          onChange={setFormId}
          __nextHasNoMarginBottom
        />
      )}
      {filteredFields.map((field) => (
        <Field
          key={field.name}
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
