import { useAddons } from "../providers/Settings";
import useTab from "./useTab";

const { useMemo } = wp.element;

const DEFAULT = {
  title: "",
  jobs: [],
  templates: [],
  bridges: [],
};

export default function useAddon() {
  const [addon] = useTab();
  const [addons, setAddons] = useAddons();

  const data = useMemo(() => {
    if (!addons[addon]) return DEFAULT;
    return { ...DEFAULT, ...addons[addon] };
  }, [addons, addon]);

  return [data, (data) => setAddons({ [addon]: data })];
}

export function useBridges() {
  const [addon, setAddon] = useAddon();
  return [addon.bridges || [], (bridges) => setAddon({ ...addon, bridges })];
}

export function useJobs() {
  const [addon, setAddon] = useAddon();
  return [addon.jobs || [], (jobs) => setAddon({ ...addon, jobs })];
}

export function useTemplates() {
  const [addon, setAddon] = useAddon();

  return [
    addon.templates || [],
    (templates) => setAddon({ ...addon, templates }),
  ];
}
