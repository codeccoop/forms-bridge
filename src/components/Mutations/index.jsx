// source
import { useForms } from "../../providers/Forms";
import MutationLayers from "./Layers";

const { Button, Modal } = wp.components;
const { useState, useMemo, useEffect, useRef } = wp.element;
const { __ } = wp.i18n;

const CSS = `.components-modal__frame.no-scrollable .components-modal__content {
  overflow: hidden;
}
.components-modal__frame.no-scrollable .components-modal__content > .components-modal__header + div {
  height: 100%;
}`;

export default function Mutations({
  formId,
  mappers,
  setMappers,
  includeFiles,
  customFields,
}) {
  const [open, setOpen] = useState(false);

  const [state, setState] = useState(mappers);

  const [forms] = useForms();
  const form = useMemo(() => {
    return forms.find((form) => form._id === formId);
  }, [forms, formId]);

  const fields = useMemo(() => {
    if (!form) return [];
    return form.fields
      .filter(({ is_file }) => includeFiles || !is_file)
      .reduce((fields, { name, label, is_file, schema }) => {
        if (includeFiles && is_file) {
          fields.push({ name, label, schema: { type: "string" } });
          fields.push({
            name: name + "_filename",
            label: name + "_filename",
            schema: { type: "string" },
          });
        } else {
          fields.push({ name, label, schema });
        }

        return fields;
      }, [])
      .concat(
        customFields.map(({ name }) => ({
          name,
          label: name,
          schema: { type: "string" },
        }))
      );
  }, [form, customFields]);

  const handleSetState = useRef((mappers) => {
    const state = mappers.map(({ from, to, cast }) => {
      return { from, to, cast };
    });

    setState(state);
  }).current;

  const onClose = () => {
    const mappers = state.filter(({ from, to, cast }) => from && to && cast);
    setMappers(mappers);
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
        disabled={!form}
        variant="secondary"
        onClick={() => setOpen(true)}
        __next40pxDefaultSize
      >
        {__("Mappers", "forms-bridge")} ({mappers.length})
      </Button>
      {open && (
        <Modal
          title={__("Mutation layers", "forms-bridge")}
          onRequestClose={onClose}
          className="no-scrollable"
        >
          <div
            style={{
              minWidth: "575px",
              minHeight: "125px",
              height: "100%",
              display: "flex",
              flexDirection: "column",
              borderTop: "1px solid",
              borderBottom: "1px solid",
            }}
          >
            <MutationLayers
              fields={fields}
              mappers={state.map((mapper, index) => ({ ...mapper, index }))}
              setMappers={handleSetState}
              done={() => setOpen(false)}
            />
          </div>
        </Modal>
      )}
    </>
  );
}
