// source
import StagePayload from "../Workflow/Payload";

const { __ } = wp.i18n;

const DIFF_MOCK = {
  enter: new Set(),
  exit: new Set(),
  mutated: new Set(),
  missing: new Set(),
};

export default function FormFields({ fields }) {
  return (
    <div
      style={{
        display: "flex",
        flexDirection: "column",
        height: "340px",
      }}
    >
      <div style={{ borderBottom: "1px solid" }}>
        <h2 style={{ marginTop: "5px" }}>{__("Submission", "forms-bridge")}</h2>
      </div>
      <div
        style={{
          flex: 1,
          height: "100%",
          overflow: "hidden auto",
          padding: "5px",
        }}
      >
        <StagePayload
          fields={fields.filter((field) => !field.is_file)}
          mappers={[]}
          showDiff={false}
          diff={DIFF_MOCK}
        />
      </div>
    </div>
  );
}
