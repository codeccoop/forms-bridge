// source
import { useApis } from "../../../../src/providers/Settings";

export default function useGSApi() {
  const [
    {
      "google-sheets-api": api = {
        authorized: false,
        client_id: "",
        client_secret: "",
        configured: false,
        form_hooks: [],
      },
    },
    patch,
  ] = useApis();

  const setApi = (field) =>
    patch({ "google-sheets-api": { ...api, ...field } });

  return [api, setApi];
}
