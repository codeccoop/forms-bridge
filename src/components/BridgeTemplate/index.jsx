// source
const { Button, Modal, SelectControl } = wp.components;
const { useState } = wp.element;
const { __ } = wp.i18n;

export default function BridgeTemplate({ templates, template, setTemplate }) {
  const [open, setOpen] = useState(false);

  const options = [{ label: "", value: "" }].concat(
    templates.map((template) => ({
      label: template.title,
      value: template.name,
    }))
  );

  return (
    <>
      <Button
        variant="primary"
        onClick={() => setOpen(true)}
        style={{ width: "150px", justifyContent: "center" }}
        __next40pxDefaultSize
      >
        {__("Template", "forms-bridge")}
      </Button>
      {open && (
        <Modal
          title={__("Bridge template", "forms-bridge")}
          onRequestClose={() => setOpen(false)}
        >
          <SelectControl
            label={__("Template", "forms-bridge")}
            value={template || ""}
            onChange={setTemplate}
            options={options}
            __nextHasNoMarginBottom
            __next40pxDefaultSize
          />
        </Modal>
      )}
    </>
  );
}
