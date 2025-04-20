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
  customFields,
}) {
  const [open, setOpen] = useState(false);

  return (
    <>
      <Button
        disabled={!form}
        variant={
          (form && workflow.length) || mutations[0].length
            ? "primary"
            : "secondary"
        }
        onClick={() => setOpen(true)}
        style={{ width: "150px", justifyContent: "center" }}
        __next40pxDefaultSize
      >
        {__("Workflow", "forms-bridge")}
      </Button>
      {open && (
        <Modal
          title={__("Submission workflow", "forms-bridge")}
          onRequestClose={() => setOpen(false)}
        >
          <WorkflowProvider
            form={form}
            mutations={mutations}
            workflow={workflow}
            customFields={customFields}
          >
            <p style={{ marginTop: "-3rem", position: "absolute", zIndex: 1 }}>
              {__(
                "Process the form submission before it is sent to the backend over the bridge",
                "forms-bridge"
              )}
            </p>
            <div
              style={{
                marginTop: "2rem",
                width: "1280px",
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
