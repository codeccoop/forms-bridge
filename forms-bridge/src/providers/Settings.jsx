import diff from "../lib/diff";
import { useError } from "./Error";
import { useLoading } from "./Loading";

const {
  createContext,
  useContext,
  useState,
  useEffect,
  useRef,
  useMemo,
  useCallback,
} = wp.element;
const apiFetch = wp.apiFetch;
const { __ } = wp.i18n;

const DEFAULTS = Object.freeze({
  state: {
    general: {
      loading: true,
      notification_receiver: "",
      addons: [],
      integrations: null,
      debug: false,
    },
    http: {
      backends: [],
      credentials: [],
    },
  },
  patch: () => {},
  submit: () => Promise.resolve(),
  fetch: () => Promise.resolve(),
});

const SettingsContext = createContext(DEFAULTS);

export default function SettingsProvider({ children }) {
  const [, setLoading] = useLoading();
  const [, setError] = useError();

  const initialState = useRef(null);
  const [state, setState] = useState(null);
  const currentState = useRef(state);
  currentState.current = state;

  const fetch = useRef(() => {
    setLoading(true);

    return apiFetch({
      path: "forms-bridge/v1/settings",
    })
      .then((state) => {
        initialState.current = state;
        currentState.current = state;
        setState(state);
      })
      .catch(() => setError(__("Settings loading error", "forms-bridge")))
      .finally(() => setLoading(false));
  }).current;

  const beforeUnload = useRef((ev) => {
    if (diff(currentState.current, initialState.current)) {
      ev.preventDefault();
      ev.returnValue = true;
    }
  }).current;

  useEffect(() => {
    window.addEventListener("beforeunload", beforeUnload);
    fetch();

    return () => {
      window.removeEventListener("beforeunload", beforeUnload);
    };
  }, []);

  useEffect(() => {
    if (window.__wpfbInvalidated === true) {
      submit(state)
        .then(() => {
          if (window.__wpfbReload) {
            window.location.reload();
          }
        })
        .finally(() => {
          window.__wpfbReload = false;
        });

      window.__wpfbInvalidated = false;
    }
  }, [state]);

  const patch = useCallback(
    (partial) => setState({ ...state, ...partial }),
    [state]
  );

  const submit = useRef((state) => {
    setLoading(true);

    return apiFetch({
      path: "forms-bridge/v1/settings",
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      data: state,
    })
      .catch(() => {
        setError(__("Settings submission error", "forms-bridge"));
        return { success: false };
      })
      .then((state) => {
        initialState.current = state;
        currentState.current = state;
        setState(state);
      })
      .finally(() => setLoading(false));
  }).current;

  const settings = state || DEFAULTS.state;

  return (
    <SettingsContext.Provider value={{ state: settings, patch, submit, fetch }}>
      {children}
    </SettingsContext.Provider>
  );
}

export function useSettings() {
  const { state, submit } = useContext(SettingsContext);
  return [state, (state) => submit(state)];
}

export function useFetchSettings() {
  const { fetch } = useContext(SettingsContext);
  return fetch;
}

export function useGeneral() {
  const {
    state: { general },
    patch,
  } = useContext(SettingsContext);
  return [general, (general) => patch({ general })];
}

export function useHttp() {
  const {
    state: { http },
    patch,
  } = useContext(SettingsContext);
  return [http, (http) => patch({ http })];
}

export function useAddons() {
  const { state, patch } = useContext(SettingsContext);

  const addons = useMemo(() => {
    return Object.keys(state).reduce((addons, setting) => {
      if (setting !== "general" && setting !== "http") {
        addons[setting] = state[setting];
      }

      return addons;
    }, {});
  }, [state]);

  return [addons, patch];
}
