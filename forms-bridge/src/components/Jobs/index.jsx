// source
import { useJobs } from "../../hooks/useAddon";
import { prependEmptyOption } from "../../lib/utils";
import { useJob, useJobConfig } from "../../providers/Jobs";
import RemoveButton from "../RemoveButton";
import JobInterface from "./Interface";
import JobModal from "./Modal";
import JobSnippet from "./Snippet";
import FieldWrapper from "../FieldWrapper";
import EditIcon from "../icons/Edit";
import CopyIcon from "../icons/Copy";
import { useError } from "../../providers/Error";
import { useFetchSettings } from "../../providers/Settings";

const { useState, useEffect, useRef, useMemo, useCallback } = wp.element;
const { PanelBody, SelectControl, Button, TabPanel, Spinner } = wp.components;
const { __ } = wp.i18n;

const TABS = [
  {
    name: "input",
    title: __("Input interface", "forms-bridge"),
  },
  {
    name: "output",
    title: __("Output interface", "forms-bridge"),
  },
  {
    name: "snippet",
    title: __("Job snippet", "forms-bridge"),
  },
];

export default function Jobs() {
  const [error] = useError();

  const fetchSettings = useFetchSettings();

  const [jobs] = useJobs();
  const [job, setJob] = useJob();
  const [config, setConfig, reset] = useJobConfig();

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

  useEffect(() => {
    if (error) {
      setEdit(false);
    }
  }, [error]);

  const names = useMemo(() => new Set(jobs.map((job) => job.name)), [jobs]);

  const copy = useCallback(() => {
    const clone = { ...config };

    clone.title += " (copy)";

    while (names.has(clone.name)) {
      clone.name += "-copy";
    }

    setConfig(clone).then(() => fetchSettings());
  }, [config, names]);

  const loading = job && !config;

  return (
    <>
      <PanelBody
        title={__("Workflow jobs", "forms-bridge")}
        initialOpen={false}
      >
        <p style={{ marginBottom: "2em" }}>
          {__("Manage and edit addon's jobs", "forms-bridge")}
        </p>
        <div style={{ display: "flex", gap: "0.5rem", marginBottom: "2rem" }}>
          <FieldWrapper>
            <SelectControl
              value={job || ""}
              onChange={setJob}
              options={jobOptions}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </FieldWrapper>
          <Button
            variant="secondary"
            style={{ width: "40px", justifyContent: "center" }}
            onClick={() => {
              setJob(null);
              setEdit(true);
            }}
            __next40pxDefaultSize
          >
            +
          </Button>
        </div>
        <JobsContent
          loading={loading}
          config={config}
          setEdit={setEdit}
          reset={reset}
          copy={copy}
        />
      </PanelBody>
      <JobModal show={edit} onClose={() => setEdit(false)} />
    </>
  );
}

function JobsContent({ loading, config, setEdit, reset, copy }) {
  const tabRef = useRef("input");
  const setTab = useRef((tab) =>
    setTimeout(() => (tabRef.current = tab))
  ).current;

  const contentRef = useRef();
  const fitToViewbox = useRef(() => {
    setTimeout(() => {
      if (!contentRef.current) return;
      if (window.scrollY < contentRef.current.offsetTop) {
        window.scrollTo({
          left: 0,
          top: contentRef.current.offsetTop,
          behavior: "smooth",
        });
      }
    }, 100);
  }).current;

  useEffect(() => {
    if (!config) return;
    fitToViewbox();
  }, [config]);

  if (loading)
    return (
      <div
        style={{
          height: "240px",
          backgroundColor: "rgb(245, 245, 245)",
          display: "flex",
          justifyContent: "center",
          alignItems: "center",
        }}
      >
        <Spinner />
      </div>
    );

  if (!config) return;
  return (
    <div
      style={{
        padding: "calc(24px) calc(32px)",
        width: "calc(100% - 64px)",
        backgroundColor: "rgb(245, 245, 245)",
      }}
    >
      <div ref={contentRef} style={{ display: "flex" }}>
        <div style={{ flex: 2 }}>
          <h3 style={{ margin: 0 }}>{config.title}</h3>
          <p>{config.description}</p>
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
              width: "40px",
            }}
            __next40pxDefaultSize
          >
            <EditIcon
              width="20"
              height="20"
              color="var(--wp-components-color-accent,var(--wp-admin-theme-color,#3858e9))"
            />
          </Button>
          <Button
            variant="primary"
            style={{
              height: "40px",
              width: "40px",
              justifyContent: "center",
              fontSize: "1.5em",
              border: "1px solid",
              padding: "6px 6px",
            }}
            onClick={copy}
            label={__("Duplaicate", "forms-bridge")}
            showTooltip
            __next40pxDefaultSize
          >
            <CopyIcon width="25" height="25" color="white" />
          </Button>
          <RemoveButton
            disabled={!config.post_id}
            label={__("Reset", "forms-bridge")}
            onClick={() => reset(config.name)}
            isDestructive={false}
            icon
          />
        </div>
      </div>
      <TabPanel tabs={TABS} onSelect={setTab}>
        {({ name }) => {
          if (name !== tabRef.current) fitToViewbox();

          return (
            <div
              style={{
                background: "white",
                padding: "calc(12px) calc(24px)",
                width: "calc(100% - 48px)",
              }}
            >
              <TabContent tab={name} config={config} />
            </div>
          );
        }}
      </TabPanel>
    </div>
  );
}

function TabContent({ tab, config }) {
  switch (tab) {
    case "input":
      return <JobInterface fields={config.input} />;
    case "output":
      return (
        <JobInterface
          fields={config.output.map((field) => ({
            ...field,
            required: !field.requires?.length,
          }))}
        />
      );
    case "snippet":
      return <JobSnippet id={config.id} snippet={config.snippet} />;
  }
}
