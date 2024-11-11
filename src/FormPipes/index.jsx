// vendor
import React from "react";
import { Button, Modal } from "@wordpress/components";
import { useState } from "@wordpress/element";

// source
import PipesTable from "./Table";
import { useI18n } from "../providers/I18n";

export default function FormPipes({ formId, pipes, setPipes }) {
  const __ = useI18n();
  const [open, setOpen] = useState(false);
  return (
    <>
      <Button
        variant="secondary"
        onClick={() => setOpen(true)}
        style={{ width: "130px", justifyContent: "center", height: "32px" }}
      >
        {__("Pipes", "wpct-erp-forms")}
      </Button>
      {open && (
        <Modal
          title={__("Form pipes", "wpct-erp-forms")}
          onRequestClose={() => setOpen(false)}
        >
          <PipesTable formId={formId} pipes={pipes} setPipes={setPipes} />
        </Modal>
      )}
    </>
  );
}
