// source
import useResponsive from "../../hooks/useResponsive";
import { useJob } from "../../providers/Jobs";
import { useWorkflowStage, useWorkflowStepper } from "../../providers/Workflow";
import JobEditor from "../JobEditor";
import WorkflowPipeline from "./Pipeline";
import WorkflowStage from "./Stage";

const { Button, Modal } = wp.components;
const { useState, useRef, useCallback } = wp.element;
const { __ } = wp.i18n;

export default function Workflow({
  workflow = [],
  setWorkflow,
  setMutationMappers,
  open,
  setOpen,
}) {
  const isResponsive = useResponsive(920);

  const [, setJob] = useJob();
  const [edit, setEdit] = useState(null);

  const [, setWorkflowStep, workflowLength] = useWorkflowStepper();
  const [fields] = useWorkflowStage();

  const onOpen = useRef(() => {
    setWorkflowStep(0);
    setOpen(true);
  }).current;

  const onClose = useCallback(() => {
    setWorkflowStep(workflowLength - 1);
    setEdit(false);
    setJob(null);
    setOpen(false);
  }, [workflowLength]);

  const onEditClose = useRef(() => {
    setJob(null);
    setEdit(false);
  }).current;

  return (
    <>
      <Button
        disabled={!fields.length}
        variant="secondary"
        onClick={onOpen}
        __next40pxDefaultSize
      >
        {__("Workflow", "forms-bridge")} ({workflow.length})
      </Button>
      {open && (
        <Modal
          title={__("Submission workflow", "forms-bridge")}
          onRequestClose={onClose}
        >
          <p
            style={{
              marginTop: "-3rem",
              position: "absolute",
              zIndex: 1,
            }}
          >
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
              flexDirection: isResponsive ? "column" : "row",
            }}
          >
            {(edit && <JobEditor close={onEditClose} />) || (
              <>
                <div
                  style={
                    isResponsive
                      ? {
                          flex: 1,
                          display: "flex",
                          flexDirection: "column",
                          borderBottom: "1px solid",
                          paddingBottom: "1rem",
                          marginBottom: "2rem",
                        }
                      : {
                          flex: 1,
                          maxWidth: "400px",
                          display: "flex",
                          flexDirection: "column",
                          height: "100%",
                          borderRight: "1px solid",
                          paddingRight: "1rem",
                          marginRight: "1rem",
                        }
                  }
                >
                  <WorkflowPipeline
                    workflow={workflow}
                    setWorkflow={setWorkflow}
                    setEdit={() => setEdit(true)}
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
                    setEdit={(job) => {
                      setJob(job);
                      setEdit(true);
                    }}
                    setMappers={(step, mappers) =>
                      setMutationMappers(step, mappers)
                    }
                  />
                </div>
              </>
            )}
          </div>
        </Modal>
      )}
    </>
  );
}
