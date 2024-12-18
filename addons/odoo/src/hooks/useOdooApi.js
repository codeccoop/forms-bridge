import { useApis } from "../../../../src/providers/Settings";

export default function useOdooApi() {
  const [{ "odoo-api": api = { databases: [], form_hooks: [] } }, patch] =
    useApis();
  const setApi = (value) => patch({ "odoo-api": value });
  return [api, setApi];
}
