// source
import CustomFieldsTable from "./Table";

const { Button, Modal } = wp.components;
const { useState, useEffect, useRef } = wp.element;
const { __ } = wp.i18n;

const CSS = `.components-modal__frame.no-scrollable .components-modal__content {
  overflow: hidden;
}
.components-modal__frame.no-scrollable .components-modal__content > .components-modal__header + div {
  height: 100%;
}`;

export default function CustomFields({ customFields, setCustomFields }) {
  const [open, setOpen] = useState(false);

  const [state, setState] = useState(customFields);

  const handleSetState = useRef((customFields) => {
    const state = customFields.map(({ name, value }) => {
      return { name, value };
    });

    setState(state);
  }).current;

  const onClose = () => {
    const customFields = state.filter(({ name, value }) => name && value);
    setCustomFields(customFields);
    setOpen(false);
  };

  const style = useRef(document.createElement("style"));
  useEffect(() => {
    style.current.appendChild(document.createTextNode(CSS));
    document.head.appendChild(style.current);

    return () => {
      document.head.removeChild(style.current);
    };
  }, []);

  return (
    <>
      <Button
        variant="secondary"
        onClick={() => setOpen(true)}
        __next40pxDefaultSize
      >
        {__("Custom fields", "forms-bridge")} ({customFields.length})
      </Button>
      {open && (
        <Modal
          title={__("Custom fields", "forms-bridge")}
          onRequestClose={onClose}
          className="no-scrollable"
        >
          <p
            style={{
              marginTop: "-3rem",
              position: "absolute",
              zIndex: 1,
            }}
          >
            {__(
              "Add custom fields to the form submission payload",
              "forms-bridge"
            )}
          </p>
          <div
            style={{
              marginTop: "2rem",
              width: "760px",
              maxWidth: "80vw",
              minHeight: "125px",
              height: "calc(100% - 2rem)",
              display: "flex",
              flexDirection: "column",
              borderTop: "1px solid",
              borderBottom: "1px solid",
            }}
          >
            <CustomFieldsTable
              customFields={state.map((constant, index) => ({
                ...constant,
                index,
              }))}
              setCustomFields={handleSetState}
            />
          </div>
        </Modal>
      )}
    </>
  );
}
