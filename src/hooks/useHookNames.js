// vendor
import { useMemo } from "@wordpress/element";

// source
import { useRestApi, useRpcApi } from "../providers/Settings";

export default function useHookNames() {
  const [{ form_hooks: restHooks }] = useRestApi();
  const [{ form_hooks: rpcHooks }] = useRpcApi();

  return useMemo(() => {
    return new Set(restHooks.concat(rpcHooks).map(({ name }) => name));
  }, [restHooks, rpcHooks]);
}
