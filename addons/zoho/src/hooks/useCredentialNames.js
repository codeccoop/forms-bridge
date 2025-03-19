import useZohoApi from "./useZohoApi";

const { useMemo } = wp.element;

export default function useCredentialNames() {
  const [{ credentials }] = useZohoApi();
  return useMemo(
    () => new Set(credentials.map(({ name }) => name)),
    [credentials]
  );
}
