// source
import { useBridges } from "../providers/Settings";

const { useMemo } = wp.element;

export default function useBridgeNames(api) {
  const bridges = useBridges(api);

  return useMemo(() => {
    return new Set(bridges.map(({ name }) => name));
  }, [bridges]);
}
