// vendor
import apiFetch from "@wordpress/api-fetch";
import { useEffect } from "@wordpress/element";
import useGSApi from "./useGSApi";

export default function useOath() {
  const [{ authorized, client_id, client_secret, configured }, update] =
    useGSApi();

  useEffect(() => {
    const query = new URLSearchParams(window.location.search);
    if (query.has("code")) {
      grant(query);
    }
  }, []);

  function revoke() {
    apiFetch({
      path: `${window.wpApiSettings.root}wp-bridges/v1/forms-bridge/gs-revoke`,
      headers: {
        "X-WP-Nonce": wpApiSettings.nonce,
      },
    }).then(({ success }) => {
      update({ authorized: !success });
    });
  }

  function connect(value) {
    if (!configured) return;
    if (!value) return revoke();

    return apiFetch({
      path: `${window.wpApiSettings.root}wp-bridges/v1/forms-bridge/gs-connect`,
      headers: {
        "X-WP-Nonce": wpApiSettings.nonce,
        "Content-Type": "application/json",
      },
    }).then(({ auth_url }) => {
      window.location.href = auth_url;
    });
  }

  function grant(query) {
    if (authorized) return;

    return apiFetch({
      method: "POST",
      path: `${window.wpApiSettings.root}wp-bridges/v1/forms-bridge/gs-grant`,
      headers: {
        "X-WP-Nonce": wpApiSettings.nonce,
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ accessCode: query.get("code") }),
    }).then(({ success }) => {
      query.delete("code");
      query.delete("scope");
      window.history.replaceState(
        {},
        "",
        `${window.location.pathname}?${query.toString()}`
      );
      update({ authorized: success });
    });
  }

  return [{ authorized, client_id, client_secret }, connect];
}
