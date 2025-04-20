import { useApis } from "../../../../src/providers/Settings";

export default function useOdooApi() {
  const [
    {
      odoo: api = {
        credentials: [],
        bridges: [],
        templates: [],
        workflow_jobs: [],
      },
    },
    patch,
  ] = useApis();
  const setApi = (odoo) => patch({ odoo });
  return [api, setApi];
}
