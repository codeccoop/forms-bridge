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
      notification_receiver: "",
      backends: [],
      addons: [],
      integrations: [],
      debug: false,
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

  const fetch = useRef(() => {
    setLoading(true);

    initialState.current = null;
    return apiFetch({
      path: "forms-bridge/v1/settings",
    })
      .then(setState)
      .catch(() => setError(__("Settings loading error", "forms-bridge")))
      .finally(() => setLoading(false));
  }).current;

  const beforeUnload = useCallback(
    (ev) => {
      if (diff(state, initialState.current)) {
        ev.preventDefault();
        ev.returnValue = true;
      }
    },
    [state]
  );

  useEffect(() => {
    window.addEventListener("beforeunload", beforeUnload);
    fetch();

    return () => {
      window.removeEventListener("beforeunload", beforeUnload);
    };
  }, []);

  useEffect(() => {
    if (initialState.current) {
      let flush = diff(
        initialState.current.general.integrations,
        state.general.integrations
      );

      flush =
        flush ||
        diff(initialState.current.general.addons, state.general.addons);

      flush =
        flush || initialState.current.general.debug !== state.general.debug;

      if (flush) submit(state);
    }

    return () => {
      initialState.current = state;
    };
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
      .then((result) => {
        if (result.success) return fetch();
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

export function useAddons() {
  const { state, patch } = useContext(SettingsContext);

  const addons = useMemo(() => {
    return Object.keys(state).reduce((addons, addon) => {
      if (addon !== "general") {
        addons[addon] = state[addon];
      }

      return addons;
    }, {});
  }, [state]);

  return [addons, patch];
}
