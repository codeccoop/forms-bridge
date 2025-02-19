// source
import PipesTable from "./Table";

const { Button, Modal } = wp.components;
const { useState } = wp.element;
const { __ } = wp.i18n;

export default function BridgePipes({ form, pipes, setPipes }) {
  const [open, setOpen] = useState(false);

  const handleSetPipes = (pipes) => {
    pipes.forEach((pipe) => {
      delete pipe.index;
    });

    setPipes(pipes);
  };

  return (
    <>
      <Button
        variant="secondary"
        onClick={() => setOpen(true)}
        style={{ width: "150px", justifyContent: "center" }}
        __next40pxDefaultSize
      >
        {__("Pipes", "forms-bridge")}
      </Button>
      {open && (
        <Modal
          title={__("Bridge pipes", "forms-bridge")}
          onRequestClose={() => setOpen(false)}
        >
          <div style={{ minWidth: "575px", minHeight: "125px" }}>
            <PipesTable
              form={form}
              pipes={pipes.map((pipe, index) => ({ ...pipe, index }))}
              setPipes={handleSetPipes}
              done={() => setOpen(false)}
            />
          </div>
        </Modal>
      )}
    </>
  );
}
