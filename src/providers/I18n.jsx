// vendor
import React from "react";
import { __ } from "@wordpress/i18n";
import {
  createContext,
  useContext,
  useState,
  useEffect,
} from "@wordpress/element";

// source
import Loading from "../Loading";

const I18nContext = createContext({});

export default function I18nProvider({ children }) {
  const [translations, setTranslations] = useState({});

  const [loading, setLoading] = useState(true);

  const lng = wp.i18n.getLocaleData()[""].lang;
  const locale = lng === "es" ? `${lng}_ES` : lng === "en" ? `${lng}_US` : lng;

  useEffect(() => {
    const wpRoot = wpApiSettings.root.replace(/wp-json\/$/, "");
    fetch(
      `${wpRoot}/wp-content/plugins/forms-bridge/languages/wpct-erp-forms-${locale}.json`
    )
      .then((res) => res.json())
      .then((translations) => setTranslations(translations))
      .finally(() => setLoading(false));
  }, []);

  return (
    <I18nContext.Provider value={translations}>
      {(loading && <Loading message={__("Loading")} />) || children}
    </I18nContext.Provider>
  );
}

export function useI18n() {
  const translations = useContext(I18nContext);

  return (text, ns) => {
    if (translations.domain !== ns) {
      return text;
    }

    return translations.locale_data[ns]?.[text]?.[0] || text;
  };
}
