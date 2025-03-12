import { useApis } from "../../../../src/providers/Settings";

export default function useRestApi() {
  const [{ financoop: api = { bridges: [], templates: [] } }, patch] =
    useApis();
  const setApi = (value) => patch({ financoop: value });
  return [api, setApi];
}
