// source
import useDebug from "../../../hooks/useDebug";
import useLogs from "../../../hooks/useLogs";

const { useEffect, useRef } = wp.element;
const {
  __experimentalSpacer: Spacer,
  ToggleControl,
  PanelBody,
  PanelRow,
} = wp.components;
const { __ } = wp.i18n;

export default function Logger() {
  const [debug, setDebug] = useDebug();
  const { logs, loading, error } = useLogs({ debug });

  const console = useRef(null);

  useEffect(() => {
    if (!console.current || console.current.scrollTop > 0) return;
    console.current.scrollTo(0, console.current.scrollHeight);
  }, [logs]);

  return (
    <PanelBody title={__("Debug", "forms-bridge")} initialOpen={!!debug}>
      <p>
        {__(
          "Activate the debug mode and open the loggin console to see bridged form submissions' logs",
          "forms-bridge"
        )}
      </p>
      <Spacer paddingBottom="5px" />
      <PanelRow>
        <ToggleControl
          label={__("Logging", "forms-bridge")}
          help={__(
            "When debug mode is activated, logs will be write to the log file and readed from there. Make sure to deactivate the debug mode once you've done to erase this file contents.",
            "forms-bridge"
          )}
          checked={!!debug}
          onChange={() => setDebug(!debug)}
          __nextHasNoMarginBottom
        />
      </PanelRow>
      {debug && (
        <>
          <Spacer paddingY="calc(8px)" />
          <PanelRow>
            <div
              ref={console}
              style={{
                height: "300px",
                width: "100%",
                background: "black",
                color: "white",
                overflowY: "auto",
                fontSize: "1.5rem",
                lineHeight: 2.5,
                fontFamily: "monospace",
                padding: "0 1rem",
              }}
            >
              {logs.map((line, i) => (
                <p key={i} style={{ margin: 0 }}>
                  {line}
                </p>
              ))}
            </div>
          </PanelRow>
        </>
      )}
    </PanelBody>
  );
}
