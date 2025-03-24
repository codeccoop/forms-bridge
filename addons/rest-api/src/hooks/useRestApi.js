import { useApis } from "../../../../src/providers/Settings";

export default function useRestApi() {
  const [
    { "rest-api": api = { bridges: [], templates: [], workflow_jobs: [] } },
    patch,
  ] = useApis();
  const setApi = (data) => patch({ "rest-api": data });
  return [api, setApi];
}
