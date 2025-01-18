// vendor
import React from "react";
import apiFetch from "@wordpress/api-fetch";
import {
  createContext,
  useContext,
  useEffect,
  useState,
} from "@wordpress/element";

const SpreadsheetsContext = createContext([]);

export default function SpreadsheetsProvider({ children }) {
  const [spreadsheets, setSpreadsheets] = useState([]);

  useEffect(() => {
    apiFetch({
      path: `${window.wpApiSettings.root}forms-bridge/v1/spreadsheets`,
      headers: {
        "X-WP-Nonce": wpApiSettings.nonce,
      },
    }).then((spreadsheets) => {
      setSpreadsheets(spreadsheets);
    });
  }, []);

  return (
    <SpreadsheetsContext.Provider value={spreadsheets}>
      {children}
    </SpreadsheetsContext.Provider>
  );
}

export function useSpreadsheets() {
  return useContext(SpreadsheetsContext);
}
