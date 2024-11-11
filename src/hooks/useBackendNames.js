// vendor
import { useMemo } from "@wordpress/element";

// source
import { useGeneral } from "../providers/Settings";

export default function useBackendNames() {
  const [{ backends }] = useGeneral();

  return useMemo(() => {
    return new Set(backends.map(({ name }) => name));
  }, [backends]);
}
