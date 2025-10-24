// source
import { useBridges } from "./useAddon";

const { useMemo } = wp.element;

export default function useBridgeNames() {
  const [bridges] = useBridges();

  return useMemo(() => {
    return new Set(bridges.map(({ name }) => name));
  }, [bridges]);
}
