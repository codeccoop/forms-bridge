import { useApis } from "../../../../src/providers/Settings";

export default function useZohoApi() {
  const [
    {
      zoho: api = {
        credentials: [],
        bridges: [],
        templates: [],
        workflow_jobs: [],
      },
    },
    patch,
  ] = useApis();
  const setApi = (data) => patch({ zoho: data });
  return [api, setApi];
}
