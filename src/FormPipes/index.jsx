// vendor
import React from "react";
import { Button, Modal } from "@wordpress/components";
import { useState } from "@wordpress/element";

// source
import PipesTable from "./Table";

export default function FormPipes({ formId, pipes, setPipes }) {
  const __ = wp.i18n.__;
  const [open, setOpen] = useState(false);
  return (
    <>
      <Button
        variant="secondary"
        onClick={() => setOpen(true)}
        style={{ width: "130px", justifyContent: "center", height: "32px" }}
      >
        {__("Pipes", "forms-bridge")}
      </Button>
      {open && (
        <Modal
          title={__("Form pipes", "forms-bridge")}
          onRequestClose={() => setOpen(false)}
        >
          <PipesTable formId={formId} pipes={pipes} setPipes={setPipes} />
        </Modal>
      )}
    </>
  );
}
