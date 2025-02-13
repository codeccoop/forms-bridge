import TemplateStep from "./Step";
import Field from "../Field";

const { useMemo } = wp.element;
const { __ } = wp.i18n;

const fieldsOrder = ["name", "form_id"];

export default function BridgeStep({ fields, data, setData }) {
  const sortedFields = useMemo(
    () =>
      fields.sort(
        (a, b) => fieldsOrder.indexOf(a.name) - fieldsOrder.indexOf(b.name)
      ),
    [fields]
  );

  return (
    <TemplateStep
      name={__("Bridge", "forms-bridge")}
      description={__("Configure the bridge", "forms-bridge")}
    >
      {sortedFields.map((field) => (
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
