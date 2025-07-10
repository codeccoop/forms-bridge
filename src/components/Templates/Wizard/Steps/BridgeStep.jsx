import TemplateStep from "./Step";
import Field from "../../Field";
import useBridgeNames from "../../../../hooks/useBridgeNames";
import { sortByNamesOrder } from "../../../../lib/utils";

const { useMemo, useState, useEffect } = wp.element;
const { __ } = wp.i18n;

const fieldsOrder = ["name"];

export default function BridgeStep({ fields, data, setData }) {
  const names = useBridgeNames();
  const [name, setName] = useState("");

  const sortedFields = useMemo(
    () => sortByNamesOrder(fields, fieldsOrder),
    [fields]
  );

  const nameConflict = useMemo(
    () => data.name !== name.trim() && names.has(name.trim()),
    [names, name]
  );

  useEffect(() => {
    if (name && !nameConflict) setData({ name });
  }, [name, nameConflict]);

  return (
    <TemplateStep
      name={__("Bridge", "forms-bridge")}
      description={__("Configure the bridge", "forms-bridge")}
    >
      <Field
        data={{
          ...sortedFields[0],
          value: name,
          onChange: setName,
        }}
        error={
          nameConflict
            ? __("This name is already in use", "forms-bridge")
            : false
        }
      />
      {sortedFields.slice(1).map((field) => (
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
