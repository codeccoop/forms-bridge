// source
import NewFormHook from "../../../../src/components/FormHooks/NewFormHook";
import TemplateWizard from "./TemplateWizard";

const { TextControl } = wp.components;
const { __ } = wp.i18n;

export default function NewFinanCoopFormHook({ add, schema }) {
  return (
    <NewFormHook add={add} schema={schema} Wizard={TemplateWizard}>
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
    </NewFormHook>
  );
}
