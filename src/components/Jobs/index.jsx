// source
import { useJobs } from "../../hooks/useAddon";
import { prependEmptyOption } from "../../lib/utils";
import { useJob, useJobConfig } from "../../providers/Jobs";
import RemoveButton from "../RemoveButton";
import JobInterface from "../Workflow/JobInterface";
import JobModal from "./Modal";
import JobSnippet from "./Snippet";

const { useState, useMemo } = wp.element;
const {
  PanelBody,
  SelectControl,
  Button,
  __experimentalSpacer: Spacer,
} = wp.components;
const { __ } = wp.i18n;

export default function Jobs() {
  const [jobs] = useJobs();
  const [job, setJob] = useJob();
  const [config, , reset] = useJobConfig();

  const [edit, setEdit] = useState(false);

  const jobOptions = useMemo(() => {
    return prependEmptyOption(
      jobs
        .map((job) => ({
          value: job.name,
          label: job.title,
        }))
        .sort((a, b) => (a.label > b.label ? 1 : -1))
    );
  }, [jobs]);

  const jobInput = useMemo(() => {
    if (!Array.isArray(config?.input)) return [];

    return config.input.map(({ name, schema, required }) => {
      return {
        name,
        schema,
        required,
        missing: false,
        mutated: false,
        optional: true,
      };
    });
  }, [config]);

  const jobOutput = useMemo(() => {
    if (!Array.isArray(config?.output)) return [];

    return config.output.map(({ name, schema, touch }) => ({
      name,
      schema,
      required: false,
      missing: false,
      mutated: touch,
      optional: true,
    }));
  }, [config]);

  const loading = job && !config;

  return (
    <>
      <PanelBody
        title={__("Workflow jobs", "forms-bridge")}
        initialOpen={false}
      >
        <p>{__("Manage and edit addon jobs", "forms-bridge")}</p>
        <div style={{ display: "flex", gap: "1em" }}>
          <div style={{ width: "250px" }}>
            <SelectControl
              value={job || ""}
              onChange={setJob}
              options={jobOptions}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </div>
          <Button
            variant="secondary"
            style={{ width: "40px", justifyContent: "center" }}
            __next40pxDefaultSize
          >
            +
          </Button>
        </div>
        <div>
          {(loading && <p>Loading...</p>) ||
            (config && (
              <>
                <Spacer paddingTop="calc(8px)" />
                <hr />
                <div style={{ display: "flex" }}>
                  <div style={{ flex: 2 }}>
                    <h2>{config.title}</h2>
                    <p style={{ margin: 0 }}>{config.description}</p>
                  </div>
                  <div
                    style={{
                      flex: 1,
                      display: "flex",
                      gap: "0.5em",
                      justifyContent: "end",
                      alignItems: "end",
                    }}
                  >
                    <Button
                      variant="secondary"
                      onClick={() => setEdit(true)}
                      style={{
                        display: "flex",
                        justifyContent: "center",
                        width: "100px",
                      }}
                      __next40pxDefaultSize
                    >
                      {__("Edit", "forms-bridge")}
                    </Button>
                    <RemoveButton
                      onClick={() => reset(job)}
                      style={{ width: "100px" }}
                    >
                      {__("Reset", "forms-bridge")}
                    </RemoveButton>
                  </div>
                </div>
                <Spacer paddingTop="calc(8px)" />
                <hr />
                <Spacer paddingTop="calc(16px)" />
                <JobInterface fields={jobInput} collapsible={false} />
                <Spacer paddingY="calc(8px)" />
                <JobInterface fields={jobOutput} collapsible={false} />
                <div
                  style={{ padding: "0 calc(8px)", border: "1px solid #ddd" }}
                >
                  <JobSnippet id={config.id} snippet={config.snippet} />
                </div>
              </>
            )) ||
            null}
        </div>
      </PanelBody>
      <JobModal show={edit} onClose={() => setEdit(false)} />
    </>
  );
}
