import { useApis } from "../../../../src/providers/Settings";

export default function useDolibarrApi() {
  const [
    {
      dolibarr: api = {
        api_keys: [],
        bridges: [],
        templates: [],
        workflow_jobs: [],
      },
    },
    patch,
  ] = useApis();
  const setApi = (data) => patch({ dolibarr: data });
  return [api, setApi];
}
