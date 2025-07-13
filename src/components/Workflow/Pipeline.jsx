import { useWorkflowStepper } from "../../providers/Workflow";
import { useJobs } from "../../hooks/useAddon";
import { prependEmptyOption } from "../../lib/utils";

const {
  __experimentalItemGroup: ItemGroup,
  __experimentalItem: Item,
  SelectControl,
  Button,
} = wp.components;
const { useMemo } = wp.element;
const { __ } = wp.i18n;

export default function WorkflowPipeline({ workflow, setWorkflow, setEdit }) {
  const [step, setStep] = useWorkflowStepper();
  const [jobs] = useJobs();

  const jobOptions = useMemo(
    () =>
      prependEmptyOption(
        jobs
          .map((job) => ({
            value: job.name,
            label: job.title,
          }))
          .sort((a, b) => (a.label > b.label ? 1 : -1))
      ),
    [jobs]
  );

  const workflowJobs = useMemo(() => {
    return workflow.map((name) => ({
      name,
      title: jobs.find((job) => job.name === name)?.title || name,
    }));
  }, [workflow, jobs]);

  const steps = useMemo(
    () =>
      [
        {
          title: __("Form submission", "forms-bridge"),
          name: "form",
        },
      ]
        .concat(workflowJobs)
        .concat([
          {
            title: __("Output payload", "forms-bridge"),
            name: "output",
          },
        ]),
    [workflowJobs]
  );

  const removeJob = (index) => {
    const newWorkflow = workflow
      .slice(0, index)
      .concat(workflow.slice(index + 1, workflow.length));

    setWorkflow(newWorkflow);

    setTimeout(() => {
      if (step - 1 === index && index === newWorkflow.length) {
        setStep(step - 1);
      }
    }, 100);
  };

  const appendJob = (index) => {
    const newWorkflow = workflow
      .slice(0, index + 1)
      .concat([jobs[0].name])
      .concat(workflow.slice(index + 1, workflow.length));

    setWorkflow(newWorkflow);
  };

  const setJob = (jobName, index) => {
    if (!jobName) return;

    const newWorkflow = workflow
      .slice(0, index)
      .concat([jobName])
      .concat(workflow.slice(index + 1, workflow.length));

    setWorkflow(newWorkflow);
  };

  return (
    <div
      style={{
        flex: 1,
        overflowY: "auto",
        display: "flex",
        flexDirection: "column",
      }}
    >
      <ItemGroup
        size="large"
        isSeparated
        style={{ maxHeight: "calc(100% - 68px)", overflowY: "auto" }}
      >
        {steps.map((job, i) => (
          <Item key={job.name + i}>
            <PipelineStep
              index={i}
              name={job.name}
              title={job.title}
              options={jobOptions}
              append={appendJob}
              remove={removeJob}
              update={setJob}
            />
          </Item>
        ))}
      </ItemGroup>
      <div
        style={{
          padding: "1rem 16px",
          marginTop: "auto",
        }}
      >
        <Button
          style={{ width: "100px", justifyContent: "center" }}
          variant="primary"
          onClick={() => setEdit(-1)}
          __next40pxDefaultSize
        >
          {__("New job", "forms-bridge")}
        </Button>
      </div>
    </div>
  );
}

function PipelineStep({ name, title, index, options, append, update, remove }) {
  const [step, setStep] = useWorkflowStepper();

  const isCurrent = step === index;
  const isFocus = isCurrent && name !== "form" && name !== "output";

  if (name === "output") {
    return (
      <div
        style={{
          display: "flex",
          alignItems: "center",
          color: isCurrent
            ? "var(--wp-components-color-accent,var(--wp-admin-theme-color,#3858e9))"
            : "inherit",
        }}
      >
        <div style={{ padding: "0.25em 0.5em" }}>
          <strong>🚀</strong>
        </div>
        <p
          style={{
            cursor: "pointer",
            textIndent: "12px",
            margin: "10px 0",
            whiteSpace: "nowrap",
          }}
          onClick={() => setStep(index)}
        >
          {title}
        </p>
      </div>
    );
  }

  return (
    <div
      style={{
        display: "flex",
        alignItems: "center",
        color: isCurrent
          ? "var(--wp-components-color-accent,var(--wp-admin-theme-color,#3858e9))"
          : "inherit",
      }}
    >
      <div style={{ padding: "0.25em 0.5em" }}>
        <strong>{index + 1}.&nbsp;</strong>
      </div>
      <div style={{ flex: 1 }}>
        {(isFocus && (
          <SelectControl
            value={name}
            onChange={(name) => update(name, index - 1)}
            options={options.filter((opt) => (index <= 1 ? true : opt.value))}
            __nextHasNoMarginBottom
            __next40pxDefaultSize
          />
        )) || (
          <p
            style={{
              cursor: "pointer",
              textIndent: "12px",
              padding: "10px 30px 10px 0",
              whiteSpace: "nowrap",
              margin: 0,
              border: index ? "1px solid #d1cfcf" : "none",
              overflow: "hidden",
              textOverflow: "ellipsis",
              position: "relative",
              maxWidth: "241px",
            }}
            onClick={() => setStep(index)}
          >
            {title}
            <span
              style={{
                display: index ? "block" : "none",
                position: "absolute",
                top: "50%",
                right: "8px",
                width: "6px",
                height: "6px",
                borderBottom: "1px solid #d1cfcf",
                borderRight: "1px solid #d1cfcf",
                transform: "translate(-50%, -50%) rotate(45deg)",
              }}
            ></span>
          </p>
        )}
      </div>
      {name !== "output" && (
        <div
          style={{
            display: "inline-flex",
            alignItems: "center",
            gap: "0.45em",
            padding: "0 0.45em 0 0.75em",
          }}
        >
          <Button
            size="compact"
            variant="secondary"
            disabled={!name}
            onClick={() => append(index - 1)}
            style={{ width: "32px" }}
            __next40pxDefaultSize
          >
            +
          </Button>
          <Button
            size="compact"
            variant="secondary"
            disabled={!name || name === "form"}
            onClick={() => remove(index - 1)}
            style={{ width: "32px" }}
            isDestructive
            __next40pxDefaultSize
          >
            -
          </Button>
        </div>
      )}
    </div>
  );
}
