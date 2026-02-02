// source
import { useDebug } from "../../hooks/useGeneral";
import useLogs from "../../hooks/useLogs";

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

  const follow = useRef(true);
  const consoleRef = useRef(null);

  useEffect(() => {
    if (!debug || !consoleRef.current) return;

    const onScroll = (ev) => {
      const consoleViewbox =
        ev.target.children[0].offsetHeight - ev.target.clientHeight;

      follow.current = ev.target.scrollTop === Math.max(0, consoleViewbox);
    };

    const el = consoleRef.current;
    el.addEventListener("scroll", onScroll);

    return () => {
      el.removeEventListener("scroll", onScroll);
    };
  }, [debug]);

  useEffect(() => {
    if (!consoleRef.current || !follow.current) return;
    consoleRef.current.scrollTo(0, consoleRef.current.scrollHeight);
  }, [logs]);

  return (
    <PanelBody title={__("Debug", "forms-bridge")} initialOpen={!!debug}>
      <p>
        {__(
          "Activate the debug mode and open the loggin console to see bridged form submissions logs",
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
              ref={consoleRef}
              style={{
                height: "500px",
                width: "100%",
                background: "black",
                color: error ? "red" : "white",
                overflowY: "auto",
                fontFamily: "monospace",
              }}
            >
              <LogLines logs={logs} error={error} loading={loading} />
            </div>
          </PanelRow>
        </>
      )}
    </PanelBody>
  );
}

function LogLines({ loading, error, logs }) {
  if (error) {
    return (
      <p style={{ textAlign: "center" }}>
        {__("Log lines loading error 🤕", "forms-bridge")}
      </p>
    );
  }

  if (loading && !logs.length) {
    return (
      <p style={{ textAlign: "center" }}>{__("Loading...", "forms-bridge")}</p>
    );
  }

  return (
    <pre
      style={{
        width: "max-content",
        paddingLeft: "1.5em",
        paddingRight: "1em",
        margin: 0,
      }}
    >
      {logs.map((line, i) => (
        <p key={i} style={{ margin: 0, fontSize: "12px" }}>
          {line}
        </p>
      ))}
    </pre>
  );
}
