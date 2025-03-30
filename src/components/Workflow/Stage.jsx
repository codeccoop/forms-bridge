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
              checked={!showMutations && !skipped}
              label={__("Before mappers", "forms-bridge")}
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
  const [showMutations, setShowMutations] = useState(true);
  const [mode, setMode] = useState("payload");

  const skipped = useMemo(() => {
    return diff.missing.values().some(() => true);
  }, [diff]);

  useEffect(() => {
    if (mode === "mappers") {
      setMode("payload");
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

  const outputFields = useMemo(() => {
    let output;
    if (!showMutations) {
      output = fields;
    } else {
      output = payloadToFields(applyMappers(fieldsToPayload(fields), mappers));
      mappers
        .map((m) => m)
        .reverse()
        .forEach(({ to, from }) => {
          if (diff.enter.has(from)) {
            diff.enter.delete(from);
            diff.enter.add(to);
          } else if (diff.mutated.has(from)) {
            diff.mutated.delete(from);
            diff.mutated.add(to);
          }
        });
    }

    if (showDiff) {
      output.forEach((field) => {
        field.enter = diff.enter.has(field.name);
        field.mutated = diff.mutated.has(field.name);
        field.exit = false;
      });

      diff.exit.values().forEach((name) => {
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
  }, [fields, mappers, showMutations, showDiff]);

  const jobInputs = useMemo(() => {
    if (!workflowJob) return [];

    return workflowJob.input.map(({ name, type, required }) => {
      return {
        name,
        missing: diff.missing.has(name),
        optional:
          !required &&
          !diff.exit.has(name) &&
          !outputFields.find((field) => field.name === name),
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
