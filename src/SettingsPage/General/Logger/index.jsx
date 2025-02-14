// source
import useDebug from "../../../hooks/useDebug";
import useLogs from "../../../hooks/useLogs";

const { useState, useEffect, useRef } = wp.element;
const { __experimentalSpacer: Spacer, ToggleControl, PanelRow } = wp.components;
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
    <>
      <Spacer paddyngY="calc(3px)" />
      <PanelRow>
        <ToggleControl
          label={__("Logging", "forms-bridge")}
          help={__(
            "When logging is activated, logs will be write to `wp-content/debug.log` and read from them. If your server is not hardened, this file could be public to the web with confidencial data. Make sure to deactivate debugging once you've done.",
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
    </>
  );
}
