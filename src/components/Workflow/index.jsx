// source
import WorkflowProvider from "../../providers/Workflow";
import WorkflowPipeline from "./Pipeline";
import WorkflowStage from "./Stage";

const { Button, Modal } = wp.components;
const { useState } = wp.element;
const { __ } = wp.i18n;

export default function BridgeWorkflow({
  workflow = [],
  setWorkflow,
  mappers = [],
  formId,
}) {
  const [open, setOpen] = useState(false);

  return (
    <>
      <Button
        variant={workflow.length ? "primary" : "secondary"}
        onClick={() => setOpen(true)}
        style={{ width: "150px", justifyContent: "center" }}
        __next40pxDefaultSize
      >
        {__("Workflow", "forms-bridge")}
      </Button>
      {open && (
        <Modal
          title={__("Bridge workflow", "forms-bridge")}
          onRequestClose={() => setOpen(false)}
        >
          <WorkflowProvider
            formId={formId}
            mappers={mappers}
            workflow={workflow}
          >
            <div
              style={{
                width: "920px",
                maxWidth: "80vw",
                height: "500px",
                maxHeight: "80vh",
                display: "flex",
              }}
            >
              <div
                style={{
                  flex: 1,
                  display: "flex",
                  flexDirection: "column",
                  height: "100%",
                  borderRight: "1px solid",
                  paddingRight: "1em",
                  marginRight: "1em",
                }}
              >
                <WorkflowPipeline
                  workflow={workflow}
                  setWorkflow={setWorkflow}
                />
                <div
                  style={{
                    paddingTop: "1em",
                    display: "flex",
                    justifyContent: "center",
                  }}
                >
                  <Button
                    variant="primary"
                    onClick={() => setOpen(false)}
                    style={{ width: "150px", justifyContent: "center" }}
                    __next40pxDefaultSize
                  >
                    {__("Save", "forms-bridge")}
                  </Button>
                </div>
              </div>
              <div
                style={{
                  flex: 2,
                  display: "flex",
                  flexDirection: "column",
                  height: "100%",
                }}
              >
                <WorkflowStage />
              </div>
            </div>
          </WorkflowProvider>
        </Modal>
      )}
    </>
  );
}
