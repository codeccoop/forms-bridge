// source
import { useGeneral } from "../providers/Settings";
import { useStoreSubmit } from "../providers/Store";

export default function useDebug() {
  const [general, patch] = useGeneral();
  const submit = useStoreSubmit();

  const update = (debug) => {
    patch({ ...general, debug });
    setTimeout(() => submit(), 0);
  };

  return [general.debug, update];
}
