import { useApis } from "../../../../src/providers/Settings";

export default function useRestApi() {
  const [{ "rest-api": api = { bridges: [] } }, patch] = useApis();
  const setApi = (value) => patch({ "rest-api": value });
  return [api, setApi];
}
