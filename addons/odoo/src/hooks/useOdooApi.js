import { useApis } from "../../../../src/providers/Settings";

export default function useOdooApi() {
  const [{ odoo: api = { databases: [], bridges: [] } }, patch] = useApis();
  const setApi = (odoo) => patch({ odoo });
  return [api, setApi];
}
