// vendor
import React from "react";
import { Button, Modal } from "@wordpress/components";
import { useState } from "@wordpress/element";

// source
import PipesTable from "./Table";

export default function FormPipes({ form, pipes, setPipes }) {
  const __ = wp.i18n.__;
  const [open, setOpen] = useState(false);
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
          title={__("Form pipes", "forms-bridge")}
          onRequestClose={() => setOpen(false)}
        >
          <div style={{ minWidth: "575px", minHeight: "125px" }}>
            <PipesTable
              form={form}
              pipes={pipes}
              setPipes={setPipes}
              done={() => setOpen(false)}
            />
          </div>
        </Modal>
      )}
    </>
  );
}
