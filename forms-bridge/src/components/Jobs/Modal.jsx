import JobEditor from "../JobEditor";

const { Modal } = wp.components;
const { __ } = wp.i18n;

export default function JobModal({ show, onClose }) {
  if (!show) return;

  return (
    <Modal title={__("Job editor", "forms-bridge")} onRequestClose={onClose}>
      <>
        <p
          style={{
            marginTop: "-3rem",
            position: "absolute",
            zIndex: 1,
          }}
        >
          {__(
            "Process the form submission before it is sent to the backend over the bridge",
            "forms-bridge"
          )}
        </p>
        <div
          style={{
            marginTop: "2rem",
            width: "1280px",
            maxWidth: "80vw",
            height: "500px",
            maxHeight: "80vh",
            display: "flex",
          }}
        >
          <JobEditor close={onClose} />
        </div>
      </>
    </Modal>
  );
}
