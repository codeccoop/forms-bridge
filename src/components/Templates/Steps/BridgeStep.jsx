import TemplateStep from "./Step";
import Field from "../Field";
import useBridgeNames from "../../../hooks/useBridgeNames";

const { useMemo, useState, useEffect } = wp.element;
const { __ } = wp.i18n;

const fieldsOrder = ["name", "form_id"];

export default function BridgeStep({ fields, data, setData }) {
  const bridgeNames = useBridgeNames();
  const [bridgeName, setBridgeName] = useState(data.name || "");

  const sortedFields = useMemo(
    () =>
      fields.sort((a, b) => {
        if (!fieldsOrder.includes(a.name)) {
          return 1;
        } else if (!fieldsOrder.includes(b.name)) {
          return -1;
        } else {
          fieldsOrder.indexOf(a.name) - fieldsOrder.indexOf(b.name);
        }
      }),
    [fields]
  );

  const filteredFields = useMemo(
    () => sortedFields.filter(({ name }) => name !== "name"),
    [sortedFields]
  );

  const nameField = useMemo(
    () => sortedFields.find(({ name }) => name),
    [sortedFields]
  );

  const nameConflict = useMemo(
    () => (bridgeName && bridgeNames.has(bridgeName.trim())) || false,
    [bridgeNames, bridgeName]
  );

  useEffect(() => {
    if (!data.name) return;

    if (data.name !== bridgeName) {
      setNewName(data.name);
    }
  }, [data.name]);

  useEffect(() => {
    if (!nameConflict && bridgeName) {
      setData({ name: bridgeName });
    }
  }, [bridgeName]);

  return (
    <TemplateStep
      name={__("Bridge", "forms-bridge")}
      description={__("Configure the bridge", "forms-bridge")}
    >
      <Field
        error={
          nameConflict
            ? __("This name is already in use", "forms-bridge")
            : false
        }
        data={{
          ...nameField,
          value: bridgeName || "",
          onChange: setBridgeName,
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
