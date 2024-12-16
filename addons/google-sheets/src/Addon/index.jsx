// vendor
import React from "react";
import { useEffect, useState, useRef, createPortal } from "@wordpress/element";

// source
import SettingsProvider from "../../../../src/providers/Settings";
import FormsProvider from "../../../../src/providers/Forms";
import SpreadsheetProvider from "../providers/Spreadsheets";
import Setting from "../Setting";

// assets
import logo from "../../assets/logo.png";

export default function Addon() {
  const [root, setRoot] = useState(null);

  const onShowTab = useRef((setting) => {
    if (setting === "google-sheets-api") {
      setRoot(document.getElementById(setting).querySelector(".root"));
    } else {
      setRoot(null);
    }
  }).current;

  useEffect(() => {
    wpfb.on("tab", onShowTab);
  }, []);

  useEffect(() => {
    if (!root) return;
    const img = document.querySelector("#google-sheets-api .addon-logo");
    if (!img) return;
    img.setAttribute("src", "data:image/png;base64," + logo);
    img.style.width = "21px";
  }, [root]);

  return (
    <SettingsProvider handle={["google-sheets-api"]}>
      <FormsProvider>
        <SpreadsheetProvider>
          <div>{root && createPortal(<Setting />, root)}</div>
        </SpreadsheetProvider>
      </FormsProvider>
    </SettingsProvider>
  );
}
