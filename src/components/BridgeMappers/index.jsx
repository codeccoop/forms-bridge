// source
import MappersTable from "./Table";

const { Button, Modal } = wp.components;
const { useState } = wp.element;
const { __ } = wp.i18n;

export default function BridgeMappers({ form, mappers, setMappers }) {
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
        variant="secondary"
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
              done={() => setOpen(false)}
            />
          </div>
        </Modal>
      )}
    </>
  );
}
