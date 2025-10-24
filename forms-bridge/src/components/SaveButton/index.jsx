import { useLoading } from "../../providers/Loading";
import { useError } from "../../providers/Error";
import { useSettings } from "../../providers/Settings";

const { Button } = wp.components;
const { __ } = wp.i18n;

export default function SaveButton() {
  const [loading] = useLoading();
  const [error] = useError();
  const [settings, saveSettings] = useSettings();

  return (
    <div style={{ textAlign: "right" }}>
      <Button
        variant="primary"
        onClick={() => saveSettings(settings)}
        style={{
          minWidth: "100px",
          justifyContent: "center",
          marginLeft: "auto",
        }}
        disabled={loading || error}
        __next40pxDefaultSize
      >
        {__("Save", "forms-bridge")}
      </Button>
    </div>
  );
}
