import TemplateStep from "../../../../src/components/Templates/Steps/Step";
import Field from "../../../../src/components/Templates/Field";

const { useMemo } = wp.element;
const { __ } = wp.i18n;

const fieldsOrder = ["name", "form_id"];

export default function BridgeStep({ fields, data, setData }) {
  const campaignOptions = useMemo(
    () =>
      data.campaigns.map(({ id, name }) => ({
        value: id,
        label: name,
      })),
    [data.campaigns]
  );

  const sortedFields = useMemo(
    () =>
      fields.sort(
        (a, b) => fieldsOrder.indexOf(a.name) - fieldsOrder.indexOf(b.name)
      ),
    [fields]
  );

  const campaignIdField = useMemo(
    () => sortedFields.find(({ name }) => name === "campaign_id"),
    [sortedFields]
  );

  const filteredFields = useMemo(
    () => sortedFields.filter(({ name }) => name !== "campaign_id"),
    [sortedFields]
  );

  return (
    <TemplateStep
      name={__("Bridge", "forms-bridge")}
      description={__("Configure the bridge", "forms-bridge")}
    >
      <Field
        data={{
          ...campaignIdField,
          value: data.campaign_id || "",
          type: "options",
          options: campaignOptions,
          onChange: (campaign_id) => setData({ campaign_id }),
        }}
      />
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
