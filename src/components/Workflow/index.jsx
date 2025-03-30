// source
import WorkflowProvider from "../../providers/Workflow";
import WorkflowPipeline from "./Pipeline";
import WorkflowStage from "./Stage";

const { Button, Modal } = wp.components;
const { useState } = wp.element;
const { __ } = wp.i18n;

export default function Workflow({
  workflow = [],
  setWorkflow,
  mutations = [],
  setMutationMappers,
  form,
  includeFiles,
}) {
  const [open, setOpen] = useState(false);

  return (
    <>
      <Button
        disabled={!form}
        variant={form && workflow.length ? "primary" : "secondary"}
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
            form={form}
            mutations={mutations}
            workflow={workflow}
            includeFiles={includeFiles}
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
                <WorkflowStage
                  setMappers={(step, mappers) =>
                    setMutationMappers(step, mappers)
                  }
                />
              </div>
            </div>
          </WorkflowProvider>
        </Modal>
      )}
    </>
  );
}
