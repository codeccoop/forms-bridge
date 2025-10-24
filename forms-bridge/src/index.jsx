// source
import ErrorBoundary from "./ErrorBoundary";
import ErrorProvider from "./providers/Error";
import LoadingProvider from "./providers/Loading";
import SchemasProvider from "./providers/Schemas";
import FormsProvider from "./providers/Forms";
import SettingsProvider from "./providers/Settings";
import Settings from "./components/Settings";
import SaveButton from "./components/SaveButton";

const { createRoot, useRef } = wp.element;
const { __experimentalHeading: Heading } = wp.components;

function App() {
  const adminbar = useRef(
    document.getElementById("wpadminbar").offsetHeight
  ).current;

  return (
    <div
      id="forms-bridge"
      style={{ position: "relative", minHeight: `calc(100vh - ${adminbar}px)` }}
    >
      <ErrorBoundary
        fallback={
          <div
            style={{
              height: "50vh",
              paddingLeft: "1em",
              display: "flex",
              justifyContent: "center",
              alignItems: "center",
            }}
          >
            <h1>Why do you do this to me? 😩</h1>
          </div>
        }
      >
        <ErrorProvider>
          <LoadingProvider>
            <SettingsProvider>
              <FormsProvider>
                <SchemasProvider>
                  <div
                    style={{
                      display: "flex",
                      justifyContent: "space-between",
                      paddingTop: "calc(16px)",
                      alignItems: "baseline",
                    }}
                  >
                    <Heading level={1}>Forms Bridge</Heading>
                    <SaveButton />
                  </div>
                  <Settings />
                </SchemasProvider>
              </FormsProvider>
            </SettingsProvider>
          </LoadingProvider>
        </ErrorProvider>
      </ErrorBoundary>
    </div>
  );
}

wp.domReady(() => {
  const root = createRoot(document.getElementById("forms-bridge"));
  root.render(<App />);
});
