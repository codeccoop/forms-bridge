// source
import { useIntegrations } from "../../hooks/useGeneral";
import useResponsive from "../../hooks/useResponsive";
import FormFields from "./Fields";
import FormBridges from "./Bridges";

const { useMemo } = wp.element;

export default function Form({ data, setBridges }) {
  const isResponsive = useResponsive();

  const [integrations] = useIntegrations();
  const integration = useMemo(() => {
    const name = data._id.split(":")[0];
    return integrations.find((integration) => integration.name === name);
  }, [integrations, data._id]);

  return (
    <div
      style={{
        padding: "calc(24px) calc(32px)",
        width: "calc(100% - 64px)",
        backgroundColor: "rgb(245, 245, 245)",
        display: "flex",
        flexDirection: isResponsive ? "column" : "row",
        gap: "2rem",
      }}
    >
      <div
        style={{
          display: "flex",
          flexDirection: "column",
          gap: "0.5rem",
          width: isResponsive ? "auto" : "201px",
        }}
      >
        <img
          src={integration.logo}
          height="40"
          style={{
            objectFit: "contain",
            objectPosition: "left",
            marginBottom: "5px",
          }}
        />
        <h4 style={{ margin: 0, fontSize: "1.3em" }}>{data.title}</h4>
        <p style={{ marginTop: "-5px" }}>{data._id}</p>
      </div>
      <div
        style={
          isResponsive
            ? {}
            : {
                paddingLeft: "2rem",
                borderLeft: "1px solid",
                display: "flex",
                flexDirection: "column",
                flex: 1,
              }
        }
      >
        <FormFields fields={data.fields} />
        <div
          style={{
            paddingTop: "16px",
            display: "flex",
            flexDirection: isResponsive ? "column" : "row",
            gap: "0.5rem",
            borderTop: "1px solid",
          }}
        >
          <div style={{ display: "flex", gap: "0.5rem" }}>
            <FormBridges bridges={data.bridges} setBridges={setBridges} />
          </div>
        </div>
      </div>
    </div>
  );
}
