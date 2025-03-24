// source
import { useApis } from "../../../../src/providers/Settings";

export default function useGSApi() {
  const [
    {
      "google-sheets": api = {
        authorized: false,
        bridges: [],
        templates: [],
        workflow_jobs: [],
      },
    },
    patch,
  ] = useApis();

  const setApi = (data) => patch({ "google-sheets": data });

  return [api, setApi];
}
