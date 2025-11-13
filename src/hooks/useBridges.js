import { useApis } from "../providers/Settings";

const { useMemo } = wp.element;

export default function useBridges() {
  const [apis] = useApis();

  return useMemo(() => {
    return Object.keys(apis).reduce((bridges, api) => {
      return bridges.concat(apis[api].backends || []);
    }, []);
  }, [apis]);
}
