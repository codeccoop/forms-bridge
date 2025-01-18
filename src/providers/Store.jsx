// vendor
import React from "react";
import apiFetch from "@wordpress/api-fetch";
import { createContext, useContext, useEffect } from "@wordpress/element";

const StoreContext = createContext(() => {});

export default function StoreProvider({ children, setLoading }) {
  const fetchSettings = () => {
    setLoading(true);
    return apiFetch({
      path: `${window.wpApiSettings.root}forms-bridge/v1/settings`,
      headers: {
        "X-WP-Nonce": wpApiSettings.nonce,
      },
    })
      .then((settings) => {
        wpfb.emit("fetch", settings);
      })
      .finally(() => {
        setLoading(false);
      });
  };

  const fetchForms = () => {
    setLoading(true);
    return apiFetch({
      path: `${window.wpApiSettings.root}forms-bridge/v1/forms`,
      headers: {
        "X-WP-Nonce": wpApiSettings.nonce,
      },
    })
      .then((forms) => {
        wpfb.emit("forms", forms);
      })
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    fetchForms().then(fetchSettings);
  }, []);

  const submit = () => {
    setLoading(true);

    const settings = wpfb.bus("submit", {});
    return apiFetch({
      path: `${window.wpApiSettings.root}forms-bridge/v1/settings`,
      method: "POST",
      headers: {
        "X-WP-Nonce": wpApiSettings.nonce,
      },
      data: settings,
    }).then(fetchSettings);
  };

  return (
    <StoreContext.Provider value={submit}>{children}</StoreContext.Provider>
  );
}

export function useStoreSubmit() {
  return useContext(StoreContext);
}
