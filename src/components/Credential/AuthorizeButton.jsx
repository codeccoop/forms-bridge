import { useLoading } from "../../providers/Loading";
import { useError } from "../../providers/Error";
import { useFetchSettings } from "../../providers/Settings";
import { restUrl } from "../../lib/utils";

const { useMemo } = wp.element;
const { Button } = wp.components;
const apiFetch = wp.apiFetch;
const { __ } = wp.i18n;

export default function AuthorizeButton({ addon, data }) {
  const [loading, setLoading] = useLoading();
  const [error, setError] = useError();

  const fetchSettings = useFetchSettings();

  const authorized = useMemo(() => {
    if (!!data.refresh_token) return true;
    else if (!(data.access_token && data.expires_at)) return false;

    let expirationDate = new Date(data.expires_at);
    if (expirationDate.getFullYear() === 1970) {
      expirationDate = new Date(data.expires_at * 1000);
    }

    return Date.now() < expirationDate.getTime();
  }, [data.access_token, data.expires_at]);

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
      .then(({ success, data }) => {
        if (!success) throw "error";

        const { url, params } = data;
        const form = document.createElement("form");
        form.action = url;
        form.method = "GET";
        form.target = "_blank";

        form.innerHTML = Object.keys(params).reduce((html, name) => {
          const value = params[name];
          if (!value) return html;
          return html + `<input name="${name}" value="${value}" />`;
        }, "");

        form.style.visibility = "hidden";
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
      })
      .catch(() => setError(""))
      .finally(() => setLoading(false));
  };

  if (authorized) {
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
