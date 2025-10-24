import { useLoading } from "../../providers/Loading";
import { useError } from "../../providers/Error";
import { useFetchSettings } from "../../providers/Settings";
import { restUrl } from "../../lib/utils";

const { Button } = wp.components;
const apiFetch = wp.apiFetch;
const { __ } = wp.i18n;

export default function AuthorizeButton({ addon, data }) {
  const [loading, setLoading] = useLoading();
  const [error, setError] = useError();

  const fetchSettings = useFetchSettings();

  const revoke = () => {
    setLoading(true);

    apiFetch({
      path: "http-bridge/v1/oauth/revoke",
      method: "POST",
      data: { credential: data },
    })
      .then(() => fetchSettings())
      .catch(() => setError(""))
      .finally(() => setLoading(false));
  };

  const authorize = () => {
    setLoading(true);

    apiFetch({
      path: "http-bridge/v1/oauth/grant",
      method: "POST",
      data: { credential: data },
    })
      .then(({ success }) => {
        if (!success) throw "error";

        const form = document.createElement("form");
        form.action = data.oauth_url + "/auth";
        form.method = "GET";
        form.target = "_blank";

        let innerHTML = `
<input name="client_id" value="${data.client_id}" />
<input name="response_type" value="code" />
<input name="redirect_uri" value="${restUrl("http-bridge/v1/oauth/redirect")}" />
<input name="access_type" value="offline" />
<input name="state" value="${btoa(addon)}" />
`;

        if (data.scope) {
          innerHTML += `<input name="scope" value="${data.scope}" />`;
        }

        form.innerHTML = innerHTML;

        form.style.visibility = "hidden";
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
      })
      .catch(() => setError(""))
      .finally(() => setLoading(false));
  };

  if (data.refresh_token) {
    return (
      <Button
        onClick={revoke}
        variant="secondary"
        isDestructive
        disabled={loading || error}
        style={{
          justifyContent: "center",
          marginLeft: "auto",
        }}
        __next40pxDefaultSize
      >
        {__("Revoke", "forms-bridge")}
      </Button>
    );
  }

  return (
    <Button
      variant="primary"
      onClick={authorize}
      disabled={loading || error}
      style={{
        justifyContent: "center",
        marginLeft: "auto",
      }}
      __next40pxDefaultSize
    >
      {__("Authorize", "forms-bridge")}
    </Button>
  );
}
