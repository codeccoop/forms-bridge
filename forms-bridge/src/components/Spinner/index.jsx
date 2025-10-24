export default function Spinner({ show }) {
  if (!show) return;

  return (
    <div
      style={{
        position: "absolute",
        zIndex: 10,
        top: "0px",
        left: "0px",
        right: "0px",
        bottom: "0px",
        margin: "auto",
        backdropFilter: "blur(0.5px)",
        backgroundColor: "#ffffff1f",
        display: "flex",
        justifyContent: "center",
        alignItems: "center",
      }}
    >
      <img
        src="/wp-content/plugins/forms-bridge/assets/spinner.gif"
        height="67px"
        width="67px"
      />
    </div>
  );
}
