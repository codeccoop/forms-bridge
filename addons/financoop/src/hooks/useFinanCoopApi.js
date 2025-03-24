import { useApis } from "../../../../src/providers/Settings";

export default function useRestApi() {
  const [
    { financoop: api = { bridges: [], templates: [], workflow_jobs: [] } },
    patch,
  ] = useApis();
  const setApi = (value) => patch({ financoop: value });
  return [api, setApi];
}
