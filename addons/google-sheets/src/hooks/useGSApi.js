// source
import { useApis } from "../../../../src/providers/Settings";

export default function useGSApi() {
  const [
    {
      "google-sheets": api = {
        authorized: false,
        bridges: [],
      },
    },
    patch,
  ] = useApis();

  const setApi = (api) => patch({ "google-sheets": api });

  return [api, setApi];
}
