import useOdooApi from "./useOdooApi";

const { useMemo } = wp.element;

export default function useCredentialNames() {
  const [{ credentials }] = useOdooApi();
  return useMemo(
    () => new Set(credentials.map(({ name }) => name)),
    [credentials]
  );
}
