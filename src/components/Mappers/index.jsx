// source
import MappersTable from "./Table";

const { Button, Modal } = wp.components;
const { useState } = wp.element;
const { __ } = wp.i18n;

export default function Mappers({ form, mappers, setMappers, includeFiles }) {
  const [open, setOpen] = useState(false);

  const handleSetMappers = (mappers) => {
    mappers.forEach((pipe) => {
      delete pipe.index;
    });

    setMappers(mappers);
  };

  return (
    <>
      <Button
        variant={mappers.length ? "primary" : "secondary"}
        onClick={() => setOpen(true)}
        style={{ width: "150px", justifyContent: "center" }}
        __next40pxDefaultSize
      >
        {__("Mappers", "forms-bridge")}
      </Button>
      {open && (
        <Modal
          title={__("Bridge mappers", "forms-bridge")}
          onRequestClose={() => setOpen(false)}
        >
          <div style={{ minWidth: "575px", minHeight: "125px" }}>
            <MappersTable
              form={form}
              mappers={mappers.map((pipe, index) => ({ ...pipe, index }))}
              setMappers={handleSetMappers}
              includeFiles={includeFiles}
              done={() => setOpen(false)}
            />
          </div>
        </Modal>
      )}
    </>
  );
}
