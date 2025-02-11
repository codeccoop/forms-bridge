const apiFetch = wp.apiFetch;
const { createContext, useContext, useEffect, useRef } = wp.element;
const { __ } = wp.i18n;

const StoreContext = createContext(() => {});

export default function StoreProvider({ children }) {
  const fetchSettings = () => {
    wpfb.emit("loading", true);

    return apiFetch({
      path: "forms-bridge/v1/settings",
    })
      .then((settings) => wpfb.emit("fetch", settings))
      .finally(() => wpfb.emit("loading", false));
  };

  const fetchForms = () => {
    wpfb.emit("loading", true);

    return apiFetch({
      path: "forms-bridge/v1/forms",
    })
      .then((forms) => wpfb.emit("forms", forms))
      .catch(() =>
        wpfb.emit("error", __("Load settings error", "forms-bridge"))
      )
      .finally(() => wpfb.emit("loading", true));
  };

  const onFlush = useRef(() => fetchSettings()).current;

  useEffect(() => {
    fetchForms().then(fetchSettings);
    wpfb.on("flushStore", onFlush);

    return () => {
      wpfb.off("flushStore", onFlush);
    };
  }, []);

  const submit = () => {
    wpfb.emit("loading", true);

    const settings = wpfb.bus("submit", {});
    return apiFetch({
      path: "forms-bridge/v1/settings",
      method: "POST",
      data: settings,
    })
      .then(fetchSettings)
      .catch(() =>
        wpfb.emit("error", __("Save settings error", "forms-bridge"))
      )
      .finally(() => wpfb.emit("loading", false));
  };

  return (
    <StoreContext.Provider value={{ submit, fetch: fetchSettings }}>
      {children}
    </StoreContext.Provider>
  );
}

export function useStoreSubmit() {
  const { submit } = useContext(StoreContext);
  return submit;
}

export function useStoreFetch() {
  const { fetch } = useContext(StoreContext);
  return fetch;
}
