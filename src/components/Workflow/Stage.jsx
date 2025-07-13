import {
  useWorkflowJob,
  useWorkflowStage,
  useWorkflowStepper,
} from "../../providers/Workflow";
import MutationLayers from "../Mutations/Layers";
import {
  applyMappers,
  fieldsToPayload,
  payloadToFields,
} from "../../lib/payload";
import WorkflowStageField from "./StageField";
import WorkflowJobInterface from "./JobInterface";
import JsonFinger from "../../lib/JsonFinger";
import JobSnippet from "../Jobs/Snippet";

const {
  __experimentalItemGroup: ItemGroup,
  __experimentalItem: Item,
  ToggleControl,
  Button,
} = wp.components;
const { useState, useMemo, useEffect } = wp.element;
const { __ } = wp.i18n;

export default function WorkflowStage({ setEdit, setMappers }) {
  const [step, _, outputStep] = useWorkflowStepper();
  const workflowJob = useWorkflowJob();
  const [fields = [], diff] = useWorkflowStage();

  const [showDiff, setShowDiff] = useState(false);
  const [showMutations, setShowMutations] = useState(false);
  const [mode, setMode] = useState("payload");

  const skipped = useMemo(() => {
    return Array.from(diff.missing).length > 0;
  }, [diff]);

  useEffect(() => {
    if (mode !== "payload") {
      setMode("payload");
    }

    if (showMutations) {
      setShowMutations(false);
    }
  }, [step]);

  const mappers = useMemo(() => {
    if (!workflowJob) return [];
    return workflowJob.mappers;
  }, [workflowJob]);

  const validMappers = useMemo(
    () => mappers.filter((mapper) => mapper.from && mapper.to),
    [mappers]
  );

  const switchMode = (target) => {
    if (mode !== target) {
      setMode(target);
    } else {
      setMode("payload");
    }
  };

  const handleSetMappers = (mappers) => {
    mappers.forEach((mapper) => {
      delete mapper.index;
    });

    setMappers(step, mappers);
  };

  const outputDiff = useMemo(() => {
    if (!showMutations) return diff;

    const outputDiff = Object.fromEntries(
      Object.entries(diff).map(([key, set]) => [key, new Set(set)])
    );

    mappers
      .map((m) => m)
      .reverse()
      .forEach((mapper) => {
        const [from] = JsonFinger.parse(mapper.from);
        const [to] = JsonFinger.parse(mapper.to);

        if (outputDiff.enter.has(from)) {
          outputDiff.enter.delete(from);
          outputDiff.enter.add(to);
        } else {
          if (outputDiff.mutated.has(from)) {
            outputDiff.mutated.delete(from);
            outputDiff.mutated.add(to);
          }

          if (outputDiff.touched.has(from)) {
            outputDiff.touched.delete(from);
            outputDiff.touched.add(to);
          }
        }
      });

    return outputDiff;
  }, [diff, showMutations, mappers]);

  const outputFields = useMemo(() => {
    let output;
    if (mode === "mappers" || !showMutations) {
      output = fields.map((field) => ({ ...field }));
    } else {
      output = payloadToFields(applyMappers(fieldsToPayload(fields), mappers));
    }

    if (showDiff) {
      output.forEach((field) => {
        field.enter = outputDiff.enter.has(field.name);
        field.mutated = outputDiff.mutated.has(field.name);
        field.touched = outputDiff.touched.has(field.name);
        field.exit = false;
      });

      Array.from(outputDiff.exit).forEach((name) => {
        output.push({
          name,
          schema: { type: "null" },
          enter: false,
          mutated: false,
          touched: false,
          exit: true,
        });
      });
    }

    return output;
  }, [mode, fields, mappers, showMutations, showDiff, outputDiff]);

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

  if (!workflowJob && step > 0 && step < outputStep) {
    return <p>{__("Loading", "forms-bridge")}</p>;
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
          <h2 style={{ margin: 0, paddingRight: "1rem" }}>{jobTitle}</h2>
          {step < outputStep && (
            <div style={{ width: "max-content", flexShrink: 0 }}>
              <ToggleControl
                __nextHasNoMarginBottom
                checked={showMutations && !skipped && mode === "payload"}
                label={__("After mutations", "forms-bridge")}
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
            <ItemGroup size="large" isSeparated>
              {outputFields.map((field, i) => (
                <Item key={field.name + i}>
                  <WorkflowStageField {...field} showDiff={showDiff} />
                </Item>
              ))}
            </ItemGroup>
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
            {__("Mutations (%s)", "forms-bridge").replace(
              "%s",
              validMappers.length
            )}
          </Button>
        </div>
        <Button
          style={{ width: "100px", justifyContent: "center" }}
          variant="secondary"
          disabled={step === 0 || step === outputStep}
          onClick={() => setEdit(true)}
          __next40pxDefaultSize
        >
          {__("Edit", "forms-bridge")}
        </Button>
      </div>
    </div>
  );
}
