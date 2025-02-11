// source
import SettingsProvider from "../../../../src/providers/Settings";
import FormsProvider from "../../../../src/providers/Forms";
import TemplatesProvider from "../../../../src/providers/Templates";
import Setting from "../Setting";

// assets
import logo from "../../assets/logo.png";

const { useEffect, useState, useRef, createPortal } = wp.element;

export default function Addon() {
  const [root, setRoot] = useState(null);

  const onShowApi = useRef((api) => {
    if (api === "rest-api") {
      setRoot(document.getElementById(api).querySelector(".root"));
    } else {
      setRoot(null);
    }
  }).current;

  useEffect(() => {
    wpfb.on("api", onShowApi);

    return () => {
      wpfb.off("api", onShowApi);
    };
  }, []);

  useEffect(() => {
    if (!root) return;
    const img = document.querySelector("#rest-api .addon-logo");
    if (!img) return;
    img.setAttribute("src", "data:image/png;base64," + logo);
    img.style.width = "70px";
  }, [root]);

  return (
    <SettingsProvider handle={["rest-api"]}>
      <FormsProvider>
        <TemplatesProvider>
          <div>{root && createPortal(<Setting />, root)}</div>
        </TemplatesProvider>
      </FormsProvider>
    </SettingsProvider>
  );
}
