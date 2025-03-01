// source
import { useForms } from "../../providers/Forms";
import { useGeneral } from "../../providers/Settings";
import useBridgeNames from "../../hooks/useBridgeNames";
import Templates from "../Templates";

const {
  TextControl,
  SelectControl,
  Button,
  __experimentalSpacer: Spacer,
} = wp.components;
const { useState, useMemo } = wp.element;
const { __ } = wp.i18n;

export default function NewBridge({
  add,
  schema,
  Wizard,
  children = () => {},
}) {
  const [{ backends }] = useGeneral();
  const backendOptions = [{ label: "", value: "" }].concat(
    backends.map(({ name }) => ({
      label: name,
      value: name,
    }))
  );

  const forms = useForms();
  const formOptions = [{ label: "", value: "" }].concat(
    forms.map(({ _id, title }) => ({
      label: title,
      value: _id,
    }))
  );

  const bridgeNames = useBridgeNames();

  const [name, setName] = useState("");
  const [backend, setBackend] = useState("");
  const [formId, setFormId] = useState("");
  const [nameConflict, setNameConflict] = useState(false);
  const [customFields, setCustomFields] = useState({});
  const customFieldsSchema = useMemo(
    () =>
      schema.filter((field) => !["name", "backend", "form_id"].includes(field)),
    [schema]
  );

  const handleSetName = (name) => {
    setNameConflict(bridgeNames.has(name.trim()));
    setName(name);
  };

  const onClick = () => {
    add({
      ...customFields,
      name: name.trim(),
      backend,
      form_id: formId,
      mappers: [],
    });

    setName("");
    setBackend("");
    setFormId("");
    setNameConflict(false);
    setCustomFields({});
  };

  const disabled = useMemo(
    () =>
      !(
        name &&
        !nameConflict &&
        (backend || !schema.includes("backend")) &&
        (formId || !schema.includes("form_id")) &&
        customFieldsSchema.reduce(
          (valid, field) => valid && customFields[field],
          true
        )
      ),
    [name, backend, formId, customFields, customFieldsSchema]
  );

  return (
    <div
      style={{
        padding: "calc(24px) calc(32px)",
        width: "calc(100% - 64px)",
        backgroundColor: "rgb(245, 245, 245)",
      }}
    >
      <div
        style={{
          display: "flex",
          gap: "1em",
          flexWrap: "wrap",
        }}
      >
        <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
          <TextControl
            label={__("Name", "forms-bridge")}
            help={
              nameConflict
                ? __("This name is already in use", "forms-bridge")
                : ""
            }
            value={name}
            onChange={handleSetName}
            __nextHasNoMarginBottom
            __next40pxDefaultSize
          />
        </div>
        {schema.includes("backend") && (
          <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
            <SelectControl
              label={__("Backend", "forms-bridge")}
              value={backend}
              onChange={setBackend}
              options={backendOptions}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </div>
        )}
        {schema.includes("form_id") && (
          <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
            <SelectControl
              label={__("Form", "forms-bridge")}
              value={formId}
              onChange={setFormId}
              options={formOptions}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </div>
        )}
        {children({
          data: customFields,
          update: (customFields) => setCustomFields(customFields),
        })}
      </div>
      <Spacer paddingY="calc(8px)" />
      <div
        style={{
          display: "flex",
          gap: "1em",
          flexWrap: "wrap",
        }}
      >
        <Button
          variant="primary"
          onClick={() => onClick()}
          style={{ width: "150px", justifyContent: "center" }}
          disabled={disabled}
          __next40pxDefaultSize
        >
          {__("Add", "forms-bridge")}
        </Button>
        <Templates Wizard={Wizard} />
      </div>
    </div>
  );
}
