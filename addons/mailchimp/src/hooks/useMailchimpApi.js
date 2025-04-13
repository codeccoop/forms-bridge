import { useApis } from "../../../../src/providers/Settings";

export default function useMailchimpApi() {
  const [
    { mailchimp: api = { bridges: [], templates: [], workflow_jobs: [] } },
    patch,
  ] = useApis();
  const setApi = (data) => patch({ mailchimp: data });
  return [api, setApi];
}
