import TemplateStep from "../../../../src/components/Templates/Steps/Step";
import Field from "../../../../src/components/Templates/Field";
import { useSpreadsheets } from "../providers/Spreadsheets";

const { SelectControl } = wp.components;
const { useMemo } = wp.element;
const { __ } = wp.i18n;

export default function DatabaseStep({ fields, data, setData }) {
  const spreadsheets = useSpreadsheets();

  const sheetOptions = [{ label: "", value: "" }].concat(
    spreadsheets.map(({ id, title }) => ({ label: title, value: id }))
  );

  const tabField = useMemo(
    () => fields.find(({ name }) => name === "tab"),
    [fields]
  );

  return (
    <TemplateStep
      name={__("Database", "forms-bridge")}
      description={__(
        "Select the spreadsheet do you want to use as backend",
        "forms-bridge"
      )}
    >
      <SelectControl
        label={__("Select a Spreadsheet", "forms-bridge")}
        value={data.id || ""}
        options={sheetOptions}
        onChange={(id) => setData({ id })}
        __nextHasNoMarginBottom
      />
      <Field
        data={{
          ...tabField,
          value: data.tab || "",
          onChange: (tab) => setData({ tab }),
        }}
      />
    </TemplateStep>
  );
}
