import {
  useWorkflowJob,
  useWorkflowStage,
  useWorkflowStepper,
} from "../../providers/Workflow";
import MutationLayers from "../Mutations/Layers";
import WorkflowJobInterface from "./JobInterface";
import JobSnippet from "../Jobs/Snippet";
import StagePayload from "./Payload";

const { ToggleControl, Button, Spinner } = wp.components;
const { useState, useMemo, useEffect, useCallback, useRef } = wp.element;
const { __ } = wp.i18n;

export default function WorkflowStage({
  mode,
  setMode,
  setEdit,
  setMappers: setJobMappers,
}) {
  const [step, _, outputStep] = useWorkflowStepper();
  const workflowJob = useWorkflowJob();
  const [fields = [], diff] = useWorkflowStage();

  const [showDiff, setShowDiff] = useState(false);
  const [showMutations, setShowMutations] = useState(false);

  const skipped = useMemo(() => {
    return Array.from(diff.missing).length > 0;
  }, [diff]);

  const stepRef = useRef(step);

  useEffect(() => {
    if (stepRef.current !== step) {
      if (mode !== "payload") {
        setMode("payload");
      }
    }

    return () => {
      stepRef.current = step;
    };
  }, [step, mode]);

  const jobMappers = useMemo(() => {
    return workflowJob?.mappers || [];
  }, [workflowJob]);

  const [mappers, setMappers] = useState(jobMappers);
  useEffect(() => {
    setMappers(jobMappers);
  }, [jobMappers]);

  const validMappers = useMemo(
    () => mappers.filter((mapper) => mapper.from && mapper.to),
    [mappers]
  );

  const switchMode = useCallback(
    (target) => {
      if (mode !== target) {
        setMode(target);
      } else {
        setMode("payload");
      }
    },
    [mode]
  );

  const handleSetMappers = useRef((mappers) => {
    mappers.forEach((mapper) => {
      delete mapper.index;
    });

    setMappers(mappers);
  }).current;

  const jobInputs = useMemo(() => {
    if (!Array.isArray(workflowJob?.input)) return [];

    return workflowJob.input.map(({ name, schema, required }) => {
      return {
        name,
        schema,
        required,
        missing: diff.missing.has(name),
        mutated: diff.mutated.has(name),
        optional:
          !required &&
          !diff.exit.has(name) &&
          (!fields.find((field) => field.name === name) ||
            diff.enter.has(name)),
      };
    });
  }, [workflowJob]);

  const jobTitle = useMemo(() => {
    if (!workflowJob) return "";

    if (skipped) {
      return `${workflowJob.title} (${__("Skipped", "forms-bridge")})`;
    }

    return workflowJob.title;
  }, [skipped, workflowJob]);

  const modeRef = useRef(mode);

  if (mode !== modeRef.current && modeRef.current === "mappers") {
    setJobMappers(
      step,
      mappers.filter(({ from, to }) => to && from)
    );
  }

  modeRef.current = mode;

  if (!workflowJob && step > 0 && step < outputStep) {
    return (
      <div
        style={{
          display: "flex",
          justifyContent: "center",
          alignItems: "center",
          height: "100%",
        }}
      >
        <Spinner />
      </div>
    );
  }

  return (
    <div style={{ display: "flex", flexDirection: "column", height: "100%" }}>
      <div style={{ borderBottom: "1px solid", paddingBottom: "1.5em" }}>
        <div
          style={{
            display: "inline-flex",
            width: "100%",
            alignItems: "center",
            justifyContent: "space-between",
          }}
        >
          <h3 style={{ margin: 0, paddingRight: "1rem" }}>{jobTitle}</h3>
          {step < outputStep && (
            <div style={{ width: "max-content", flexShrink: 0 }}>
              <ToggleControl
                __nextHasNoMarginBottom
                checked={showMutations && !skipped && mode === "payload"}
                label={__("Show mutations", "forms-bridge")}
                onChange={() => setShowMutations(!showMutations)}
                disabled={
                  skipped || mode === "mappers" || validMappers.length === 0
                }
              />
            </div>
          )}
        </div>
        <div
          style={{
            display: "inline-flex",
            width: "100%",
            justifyContent: "space-between",
          }}
        >
          <p style={{ marginTop: "0.5em", paddingRight: "1rem" }}>
            {workflowJob?.description || ""}
          </p>
          {step < outputStep && (
            <div
              style={{ margin: "6.5px", width: "max-content", flexShrink: 0 }}
            >
              <ToggleControl
                __nextHasNoMarginBottom
                checked={showDiff && !skipped && mode === "payload"}
                label={__("Show diff", "forms-bridge")}
                onChange={() => setShowDiff(!showDiff)}
                disabled={skipped || mode === "mappers"}
              />
            </div>
          )}
        </div>
        <WorkflowJobInterface fields={jobInputs} />
      </div>
      <div
        style={{
          flex: 1,
          overflow: "hidden auto",
          display: "flex",
          flexDirection: "column",
          padding: "5px",
        }}
      >
        {(mode === "snippet" && workflowJob?.snippet && (
          <JobSnippet {...workflowJob} />
        )) ||
          null}
        {(mode === "mappers" && (
          <MutationLayers
            title={__("Stage mapper", "forms-bridge")}
            fields={fields}
            mappers={mappers.map((mapper, index) => ({ ...mapper, index }))}
            setMappers={handleSetMappers}
          />
        )) ||
          null}
        {(mode === "payload" && (
          <div style={{ overflowY: "auto" }}>
            <StagePayload
              fields={fields}
              mappers={mappers}
              showMutations={mode !== "mappers" && showMutations}
              showDiff={showDiff}
              diff={diff}
            />
          </div>
        )) ||
          null}
      </div>
      <div
        style={{
          display: "flex",
          justifyContent: "space-between",
          padding: "1rem 0 1rem 6px",
          borderTop: "1px solid",
        }}
      >
        <div style={{ display: "flex", gap: "0.5em" }}>
          <Button
            style={{ width: "100px", justifyContent: "center" }}
            disabled={step === 0 || step === outputStep}
            variant={mode === "snippet" ? "primary" : "secondary"}
            onClick={() => switchMode("snippet")}
            __next40pxDefaultSize
          >
            {__("Snippet", "forms-bridge")}
          </Button>
          <Button
            disabled={step === outputStep}
            variant={mode === "mappers" ? "primary" : "secondary"}
            onClick={() => switchMode("mappers")}
            __next40pxDefaultSize
          >
            {__("Mappers", "forms-bridge")} ({validMappers.length})
          </Button>
        </div>
        <Button
          style={{ width: "100px", justifyContent: "center" }}
          variant="secondary"
          disabled={step === 0 || step === outputStep}
          onClick={() => setEdit(workflowJob.name)}
          __next40pxDefaultSize
        >
          {__("Edit", "forms-bridge")}
        </Button>
      </div>
    </div>
  );
}
