import { useGeneral } from "../providers/Settings";

export default useGeneral;

function updateRegistry(from, to) {
  return from.map((item) => {
    const enabled = !!to.find(({ name }) => name === item.name)?.enabled;
    return { ...item, enabled };
  });
}

export function useAddons() {
  const [general, setGeneral] = useGeneral();

  return [
    general.addons || [],
    (addons) => {
      setGeneral({
        ...general,
        addons: updateRegistry(general.addons || [], addons),
      });
    },
  ];
}

export function useIntegrations() {
  const [general, setGeneral] = useGeneral();

  return [
    general.integrations || [],
    (integrations) => {
      setGeneral({
        ...general,
        integrations: updateRegistry(general.integrations || [], integrations),
      });
    },
  ];
}

export function useDebug() {
  const [general, setGeneral] = useGeneral();
  return [
    general.debug,
    (debug) => {
      window.__wpfbInvalidated = true;
      setGeneral({ ...general, debug });
    },
  ];
}
