// vendor
import React from "react";
import { __ } from "@wordpress/i18n";
import apiFetch from "@wordpress/api-fetch";
import {
  createContext,
  useContext,
  useState,
  useEffect,
} from "@wordpress/element";

// source
import Loading from "../Loading";

const FormsContext = createContext([]);

export default function FormsProvider({ children }) {
  const [forms, setForms] = useState([]);

  const [loading, setLoading] = useState(true);

  useEffect(() => {
    apiFetch({
      path: `${window.wpApiSettings.root}wpct/v1/erp-forms/forms`,
      headers: {
        "X-WP-Nonce": wpApiSettings.nonce,
      },
    })
      .then((forms) => setForms(forms))
      .finally(() => setLoading(false));
  }, []);

  return (
    <FormsContext.Provider value={forms}>
      {(loading && <Loading message={__("Loading")} />) || children}
    </FormsContext.Provider>
  );
}

export function useForms() {
  return useContext(FormsContext);
}
