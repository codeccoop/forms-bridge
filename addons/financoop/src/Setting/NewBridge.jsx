// source
import NewBridge from "../../../../src/components/Bridges/NewBridge";
import FinanCoopTemplateWizard from "./TemplateWizard";

const { TextControl } = wp.components;
const { __ } = wp.i18n;

export default function NewFinanCoopBridge({ add, schema }) {
  return (
    <NewBridge add={add} schema={schema} Wizard={FinanCoopTemplateWizard}>
      {({ data, update }) => (
        <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
          <TextControl
            label={__("Endpoint", "forms-bridge")}
            value={data.endpoint || ""}
            onChange={(endpoint) => update({ ...data, endpoint })}
            __nextHasNoMarginBottom
            __next40pxDefaultSize
          />
        </div>
      )}
    </NewBridge>
  );
}
