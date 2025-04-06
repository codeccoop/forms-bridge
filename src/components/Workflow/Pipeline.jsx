import { useWorkflowStepper } from "../../providers/Workflow";
import { useApiWorkflowJobs } from "../../providers/WorkflowJobs";

const {
  __experimentalItemGroup: ItemGroup,
  __experimentalItem: Item,
  SelectControl,
  Button,
} = wp.components;
const { useMemo } = wp.element;
const { __ } = wp.i18n;

export default function WorkflowPipeline({ workflow, setWorkflow }) {
  const [step, setStep] = useWorkflowStepper();
  const apiJobs = useApiWorkflowJobs();

  const jobOptions = useMemo(
    () =>
      [{ label: "", value: "" }].concat(
        apiJobs.map((job) => ({
          value: job.name,
          label: job.title,
        }))
      ),
    [apiJobs]
  );

  const workflowJobs = useMemo(() => {
    if (!workflow.length) {
      return [{ title: "", name: "" }];
    }

    return workflow.map((name) => ({
      name,
      title: apiJobs.find((job) => job.name === name)?.title || name,
    }));
  }, [workflow, apiJobs]);

  const steps = useMemo(
    () =>
      [
        {
          title: __("Initial state", "forms-bridge"),
          name: "form",
        },
      ].concat(workflowJobs),
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
      .concat([apiJobs[0].name])
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
    <div style={{ flex: 1, overflowY: "auto" }}>
      <ItemGroup size="large" isSeparated>
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
    </div>
  );
}

function PipelineStep({ name, title, index, options, append, update, remove }) {
  const [step, setStep] = useWorkflowStepper();

  const isCurrent = step === index;
  const isFocus = isCurrent && name !== "form";

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
              margin: "10px 0",
              whiteSpace: "nowrap",
            }}
            onClick={() => setStep(index)}
          >
            {title || __("Add new step", "forms-bridge")}
          </p>
        )}
      </div>
      {name !== "form" && (
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
          >
            +
          </Button>
          <Button
            size="compact"
            variant="secondary"
            isDestructive
            disabled={!name}
            onClick={() => remove(index - 1)}
          >
            -
          </Button>
        </div>
      )}
    </div>
  );
}
