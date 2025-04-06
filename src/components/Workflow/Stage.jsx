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
import WorkflowStageInterface from "./StageInterface";

const {
  __experimentalItemGroup: ItemGroup,
  __experimentalItem: Item,
  ToggleControl,
  __experimentalSpacer: Spacer,
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
  skipped,
  step,
  mode,
  mappers,
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
        {step > 0 && (
          <div style={{ width: "max-content", flexShrink: 0 }}>
            <ToggleControl
              __nextHasNoMarginBottom
              checked={showMutations && !skipped && mode === "payload"}
              label={__("After mutations", "forms-bridge")}
              onChange={() => setShowMutations(!showMutations)}
              disabled={skipped || mode === "mappers" || mappers.length === 0}
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
        {step > 0 && (
          <div style={{ margin: "6.5px", width: "max-content", flexShrink: 0 }}>
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
      {(step > 0 && <WorkflowStageInterface fields={jobInputs} />) || (
        <Spacer marginBottom="1.4em" />
      )}
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
    return Array.from(diff.missing).length > 0;
  }, [diff]);

  useEffect(() => {
    if (mode === "mappers") {
      setMode("payload");
    }

    if (step === 0) {
      setShowMutations(true);
      setShowDiff(false);
    } else if (showMutations) {
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
    if (!workflowJob) return [];

    return workflowJob.input.map(({ name, schema, required }) => {
      return {
        name,
        schema,
        missing: diff.missing.has(name),
        mutated: diff.mutated.has(name),
        optional:
          !required &&
          !diff.exit.has(name) &&
          (!fields.find((field) => field.name === name) ||
            diff.touched.has(name)),
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
        step={step}
        mode={mode}
        mappers={validMappers}
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
          <MutationLayers
            title={__("Stage mapper", "forms-bridge")}
            fields={fields}
            mappers={mappers.map((mapper, index) => ({ ...mapper, index }))}
            setMappers={handleSetMappers}
          />
        )) || (
          <div style={{ overflowY: "auto" }}>
            <ItemGroup size="large" isSeparated>
              {outputFields.map((field, i) => (
                <Item key={field.name + i}>
                  <WorkflowStageField {...field} showDiff={showDiff} />
                </Item>
              ))}
            </ItemGroup>
          </div>
        )}
      </div>
      <div
        style={{
          display: "flex",
          justifyContent: "right",
          gap: "1.5em",
          padding: "1rem 16px",
          borderTop: "1px solid",
        }}
      >
        <p
          style={{
            margin: 0,
            color: validMappers.length
              ? "var(--wp-components-color-accent,var(--wp-admin-theme-color,#3858e9))"
              : "inherit",
          }}
        >
          {__("Output mutations: %s", "forms-bridge").replace(
            "%s",
            validMappers.length
          )}
        </p>
        <ToggleControl
          disabled={skipped}
          checked={mode === "mappers"}
          onChange={switchMode}
          label={__("Show", "forms-bridge")}
          style={{ marginTop: "1px" }}
          __nextHasNoMarginBottom
        />
      </div>
    </div>
  );
}
