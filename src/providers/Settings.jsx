// vendor
import React from "react";
import {
  createContext,
  useContext,
  useState,
  useEffect,
  useRef,
} from "@wordpress/element";

import useDiff from "../hooks/useDiff";

const defaults = {
  general: {
    notification_receiver: "",
    backends: [],
    addons: {},
    integrations: {},
  },
  apis: {
    "rest-api": {
      form_hooks: [],
    },
  },
};

const SettingsContext = createContext([defaults, () => {}]);

export default function SettingsProvider({ children, handle = ["general"] }) {
  const initialState = useRef(null);
  const currentState = useRef(defaults);
  const [state, setState] = useState(defaults);
  const [reload, setReload] = useState(false);
  currentState.current = state;

  const onPatch = useRef((state) => setState(state)).current;

  const onFetch = useRef((settings) => {
    const newState = {
      general: { ...defaults.general, ...settings.general },
    };
    newState.apis = Object.fromEntries(
      Object.entries(settings)
        .filter(([key]) => key !== "general")
        .map(([key, data]) => [key, { ...defaults.apis[key], ...data }])
    );
    setState(newState);
    const previousState = initialState.current;
    initialState.current = { ...newState };
    if (previousState === null) return;

    const reload = Object.keys(newState.general.addons).reduce(
      (changed, addon) => {
        return (
          changed ||
          newState.general.addons[addon] !== previousState.general.addons[addon]
        );
      },
      false
    );
    setReload(reload);
  }).current;

  const onSubmit = useRef((bus) => {
    const state = currentState.current;
    if (handle.indexOf("general") !== -1) {
      bus.data.general = state.general;
    }
    Object.entries(state.apis).forEach(([name, value]) => {
      if (handle.indexOf(name) !== -1) {
        bus.data[name] = value;
      }
    });
  }).current;

  const beforeUnload = useRef((ev) => {
    const state = currentState.current;
    if (useDiff(state, initialState.current) && !window.__wpfbReloading) {
      ev.preventDefault();
      ev.returnValue = true;
    }
  }).current;

  useEffect(() => {
    wpfb.on("patch", onPatch);
    wpfb.on("fetch", onFetch);
    wpfb.join("submit", onSubmit);
    window.addEventListener("beforeunload", beforeUnload);

    () => {
      wpfb.on("patch", onPatch);
      wpfb.off("fetch", onFetch);
      wpfb.leave("submit", onSubmit);
      window.removeEventListener("beforeunload", beforeUnload);
    };
  }, []);

  useEffect(() => {
    if (reload) {
      window.__wpfbReloading = true;
      window.location.reload();
    }
  }, [reload]);

  const patchState = (partial) => wpfb.emit("patch", { ...state, ...partial });

  return (
    <SettingsContext.Provider value={[state, patchState]}>
      {children}
    </SettingsContext.Provider>
  );
}

export function useGeneral() {
  const [{ general }, patch] = useContext(SettingsContext);
  return [general, (general) => patch({ general })];
}

export function useApis() {
  const [{ apis }, patch] = useContext(SettingsContext);
  return [apis, (api) => patch({ apis: { ...apis, ...api } })];
}

export function useFormHooks() {
  const [apis] = useApis();

  return Object.keys(apis).reduce((formHooks, api) => {
    return formHooks.concat(apis[api].form_hooks);
  }, []);
}
