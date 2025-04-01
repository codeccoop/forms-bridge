import {
  useWorkflowJob,
  useWorkflowStage,
  useWorkflowStepper,
} from "../../providers/Workflow";
import MappersTable from "../Mappers/Table";
import { applyMappers, fieldsToPayload, payloadToFields } from "../Mappers/lib";
import WorkflowStageField from "./StageField";
import WorkflowStageInterface from "./StageInterface";

const {
  __experimentalItemGroup: ItemGroup,
  __experimentalItem: Item,
  ToggleControl,
  Button,
} = wp.components;
const { useState, useMemo, useEffect } = wp.element;
const { __ } = wp.i18n;

function WorkflowStageHeader({
  title = "",
  description = "",
  jobInputs,
  showDiff,
  setShowDiff,
  showMutations,
  setShowMutations,
  showControls,
  skipped,
}) {
  if (skipped) {
    title += ` (${__("Skipped", "forms-bridge")})`;
  }

  return (
    <div style={{ borderBottom: "1px solid", paddingBottom: "1.5em" }}>
      <div
        style={{
          display: "inline-flex",
          width: "100%",
          alignItems: "center",
          justifyContent: "space-between",
        }}
      >
        <h2 style={{ margin: 0, paddingRight: "1rem" }}>{title}</h2>
        {showControls && (
          <div style={{ width: "max-content", flexShrink: 0 }}>
            <ToggleControl
              __nextHasNoMarginBottom
              checked={showMutations && !skipped}
              label={__("After mappers", "forms-bridge")}
              onChange={() => setShowMutations(!showMutations)}
              disabled={skipped}
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
          {description}
        </p>
        {showControls && (
          <div style={{ margin: "6.5px", width: "max-content", flexShrink: 0 }}>
            <ToggleControl
              __nextHasNoMarginBottom
              checked={showDiff && !skipped}
              label={__("Show diff", "forms-bridge")}
              onChange={() => setShowDiff(!showDiff)}
              disabled={skipped}
            />
          </div>
        )}
      </div>
      <WorkflowStageInterface fields={jobInputs} />
    </div>
  );
}

export default function WorkflowStage({ setMappers }) {
  const [step] = useWorkflowStepper();
  const workflowJob = useWorkflowJob();
  const [fields = [], diff] = useWorkflowStage();

  const [showDiff, setShowDiff] = useState(false);
  const [showMutations, setShowMutations] = useState(step === 0);
  const [mode, setMode] = useState("payload");

  const skipped = useMemo(() => {
    return diff.missing.values().some(() => true);
  }, [diff]);

  useEffect(() => {
    if (mode === "mappers") {
      setMode("payload");
    }

    if (step === 0) {
      setShowMutations(true);
    } else if (showMutations) {
      setShowMutations(false);
    }
  }, [step]);

  const mappers = useMemo(() => {
    if (!workflowJob) return [];
    return workflowJob.mappers;
  }, [workflowJob]);

  const switchMode = () => {
    if (mode === "payload") {
      setMode("mappers");
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
      .forEach(({ to, from }) => {
        if (outputDiff.enter.has(from)) {
          outputDiff.enter.delete(from);
          outputDiff.enter.add(to);
        } else if (outputDiff.mutated.has(from)) {
          outputDiff.mutated.delete(from);
          outputDiff.mutated.add(to);
        }
      });

    return outputDiff;
  }, [diff, showMutations, mappers]);

  const outputFields = useMemo(() => {
    let output;
    if (mode === "mappers" || !showMutations) {
      output = fields;
    } else {
      output = payloadToFields(applyMappers(fieldsToPayload(fields), mappers));
    }

    if (showDiff) {
      output.forEach((field) => {
        field.enter = outputDiff.enter.has(field.name);
        field.mutated = outputDiff.mutated.has(field.name);
        field.exit = false;
      });

      outputDiff.exit.values().forEach((name) => {
        output.push({
          name,
          schema: { type: "null" },
          enter: false,
          mutated: false,
          exit: true,
        });
      });
    }

    return output;
  }, [mode, fields, mappers, showMutations, showDiff, outputDiff]);

  const jobInputs = useMemo(() => {
    if (!workflowJob) return [];

    return workflowJob.input.map(({ name, type, required }) => {
      return {
        name,
        missing: diff.missing.has(name),
        optional:
          !required &&
          !diff.exit.has(name) &&
          !fields.find((field) => field.name === name),
        type,
      };
    });
  }, [workflowJob]);

  if (!workflowJob && step > 0) {
    return <p>{__("Loading", "forms-bridge")}</p>;
  }

  return (
    <div style={{ display: "flex", flexDirection: "column", height: "100%" }}>
      <WorkflowStageHeader
        skipped={skipped}
        title={workflowJob?.title}
        description={workflowJob?.description}
        jobInputs={jobInputs}
        showDiff={showDiff}
        setShowDiff={setShowDiff}
        showMutations={showMutations}
        setShowMutations={setShowMutations}
        showControls={step > 0}
      />
      <div
        style={{
          flex: 1,
          overflow: "hidden auto",
          display: "flex",
          flexDirection: "column",
          padding: "5px",
        }}
      >
        {(mode === "mappers" && (
          <MappersTable
            title={__("Stage mapper", "forms-bridge")}
            fields={fields}
            mappers={mappers.map((mapper, index) => ({ ...mapper, index }))}
            setMappers={handleSetMappers}
          />
        )) || (
          <div style={{ overflowY: "auto" }}>
            <ItemGroup size="large" isSeparated>
              {outputFields.map((field) => (
                <Item key={field.name}>
                  <WorkflowStageField {...field} showDiff={showDiff} />
                </Item>
              ))}
            </ItemGroup>
          </div>
        )}
      </div>
      {step > 0 && (
        <div style={{ marginTop: "1rem" }}>
          <Button
            variant={mode === "mappers" ? "primary" : "secondary"}
            disabled={skipped}
            onClick={switchMode}
            style={{ width: "150px", justifyContent: "center" }}
            __next40pxDefaultSize
          >
            {mode === "mappers"
              ? __("Payload", "forms-bridge")
              : __("Mappers", "forms-bridge")}
          </Button>
        </div>
      )}
    </div>
  );
}
