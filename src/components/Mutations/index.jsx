// source
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

export default function Mutations({ form, mappers, setMappers, includeFiles }) {
  const [open, setOpen] = useState(false);

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
      }, []);
  }, [form]);

  const handleSetMappers = (mappers) => {
    mappers.forEach((mapper) => {
      delete mapper.index;
    });

    setMappers(mappers);
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
        variant={
          form && mappers.filter((m) => m.from).length ? "primary" : "secondary"
        }
        onClick={() => setOpen(true)}
        style={{ width: "150px", justifyContent: "center" }}
        __next40pxDefaultSize
      >
        {__("Mutations", "forms-bridge")}
      </Button>
      {open && (
        <Modal
          title={__("Mutation layers", "forms-bridge")}
          onRequestClose={() => setOpen(false)}
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
              mappers={mappers.map((mapper, index) => ({ ...mapper, index }))}
              setMappers={handleSetMappers}
              done={() => setOpen(false)}
            />
          </div>
        </Modal>
      )}
    </>
  );
}
