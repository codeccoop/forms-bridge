import {
  useWorkflowJob,
  useWorkflowStage,
  useWorkflowStepper,
} from "../../providers/Workflow";
import StagePayload from "../Workflow/Payload";

const { useEffect, useMemo } = wp.element;
const { __ } = wp.i18n;

export default function BridgePayload({ height, focus }) {
  const [, setWorkflowStep, workflowLength] = useWorkflowStepper();
  const workflowJob = useWorkflowJob();

  const mappers = useMemo(() => {
    return workflowJob?.mappers || [];
  }, [workflowJob]);

  const [fields = []] = useWorkflowStage();

  useEffect(() => {
    if (!focus) return;
    setWorkflowStep(workflowLength - 1);
  }, [focus, workflowLength]);

  if (!height) {
    return (
      <div style={{ flex: 1 }}>
        <div style={{ borderBottom: "1px solid" }}>
          <h2 style={{ marginTop: "5px" }}>{__("Payload", "forms-bridge")}</h2>
        </div>
      </div>
    );
  }

  return (
    <div
      style={{
        display: "flex",
        flexDirection: "column",
        height: `${height - 56}px`,
      }}
    >
      <div style={{ borderBottom: "1px solid" }}>
        <h2 style={{ marginTop: "5px" }}>{__("Payload", "forms-bridge")}</h2>
      </div>
      <div
        style={{
          flex: 1,
          height: "100%",
          overflow: "hidden auto",
          padding: "5px",
        }}
      >
        {(workflowJob && (
          <div style={{ overflowY: "auto" }}>
            <StagePayload fields={fields} mappers={mappers} />
          </div>
        )) || <p>{__("Loading", "forms-bridge")}...</p>}
      </div>
    </div>
  );
}
