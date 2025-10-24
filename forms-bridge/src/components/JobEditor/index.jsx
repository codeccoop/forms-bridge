// source
import { useJobs } from "../../hooks/useAddon";
import useTab from "../../hooks/useTab";
import { useJob } from "../../providers/Jobs";
import { useJobConfig } from "../../providers/Jobs";
import { useFetchSettings } from "../../providers/Settings";
import JobCodeEditor from "./CodeEditor";
import JobInterfaceEditor from "./Interface";
import JobMeta from "./Meta";
import { jobTemplate, pruneEmptyFileds, sanitizeTitle } from "./lib";

const { useState, useEffect, useMemo, useRef, useCallback } = wp.element;
const { TabPanel, Button, Spinner } = wp.components;
const { __ } = wp.i18n;

const EDITOR_TABS = [
  { title: __("Metadata", "forms-bridge"), name: "meta" },
  { title: __("Input interface", "forms-bridge"), name: "input" },
  { title: __("Output interface", "forms-bridge"), name: "output" },
  { title: __("Job snippet", "forms-bridge"), name: "snippet" },
];

export default function JobEditor({ close }) {
  const addon = useTab();

  const fetchSettings = useFetchSettings();

  const [jobs] = useJobs();
  const names = useMemo(() => new Set(jobs.map((job) => job.name)), [jobs]);

  const [job] = useJob();
  const [config, setConfig] = useJobConfig();
  const [state, setState] = useState();
  const [isLoading, setIsLoading] = useState(false);

  const nameRef = useRef(config?.name || "");

  useEffect(() => {
    nameRef.current = config?.name || "";

    if (!job) {
      setState(jobTemplate(addon));
    } else {
      setState(config);
    }
  }, [job, config]);

  const save = useCallback(
    (config) => {
      config.name = config.name.trim();

      if (!config.name) {
        config.name = sanitizeTitle(config.title);
      }

      config.input = pruneEmptyFileds(config.input);
      config.output = pruneEmptyFileds(config.output);

      setIsLoading(true);
      setConfig(config)
        .then(() =>
          nameRef.current !== config.name ? fetchSettings() : Promise.resolve()
        )
        .finally(() => {
          setIsLoading(false);
          close();
        });
    },
    [names]
  );

  if (!state?.id) return;

  return (
    <div
      style={{
        width: "100%",
        flex: 1,
        position: "relative",
      }}
    >
      <div
        style={{
          display: isLoading ? "flex" : "none",
          position: "absolute",
          zIndex: 10,
          top: 0,
          left: 0,
          width: "100%",
          height: "100%",
          background: "#ffffff88",
          justifyContent: "center",
          alignItems: "center",
        }}
      >
        <Spinner />
      </div>
      <TabPanel tabs={EDITOR_TABS}>
        {(tab) => (
          <div
            style={{
              height: "calc(452px - 4rem)",
              overflowY: "auto",
              padding: "1rem 5px 0",
            }}
          >
            <JobEditorContent
              tab={tab.name}
              job={state}
              update={(newState) => setState({ ...state, ...newState })}
            />
          </div>
        )}
      </TabPanel>
      <div
        style={{
          display: "flex",
          gap: "0.5em",
          padding: "1rem 0",
          borderTop: "1px solid",
        }}
      >
        <Button
          variant="primary"
          disabled={!state.title}
          onClick={() => save(state)}
          __next40pxDefaultSize
        >
          {nameRef.current.trim() !== state.name.trim()
            ? __("Create", "forms-bridge")
            : __("Save", "forms-bridge")}
        </Button>
        <Button
          variant="secondary"
          isDestructive
          onClick={close}
          __next40pxDefaultSize
        >
          {__("Discard", "forms-bridge")}
        </Button>
      </div>
    </div>
  );
}

function JobEditorContent({ tab, job, update }) {
  switch (tab) {
    case "meta":
      return (
        <JobMeta data={job} setData={(data) => update({ ...job, ...data })} />
      );
    case "input":
      return (
        <JobInterfaceEditor
          fields={job.input}
          setFields={(input) => update({ input })}
        />
      );
    case "output":
      return (
        <JobInterfaceEditor
          fields={job.output}
          setFields={(output) => update({ output })}
          fromFields={job.input}
        />
      );
    case "snippet":
      return (
        <JobCodeEditor
          id={job.id}
          doc={job.snippet}
          update={(snippet) => update({ ...job, snippet })}
        />
      );
  }
}
