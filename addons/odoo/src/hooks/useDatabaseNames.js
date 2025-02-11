import useOdooApi from "./useOdooApi";

const { useMemo } = wp.element;

export default function useDatabaseNames() {
  const [{ databases }] = useOdooApi();
  return useMemo(() => new Set(databases.map(({ name }) => name)), [databases]);
}
