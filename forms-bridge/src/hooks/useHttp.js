import { useHttp } from "../providers/Settings";

export default useHttp;

export function useBackends() {
  const [http, save] = useHttp();
  return [http.backends || [], (backends) => save({ ...http, backends })];
}

export function useCredentials() {
  const [http, save] = useHttp();
  return [
    http.credentials || [],
    (credentials) => save({ ...http, credentials }),
  ];
}
