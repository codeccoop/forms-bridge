import useHttp from "../../hooks/useHttp";
import Backends from "../Backends";
import Credentials from "../Credentials";

const { useEffect } = wp.element;
const { __experimentalSpacer: Spacer } = wp.components;

export default function HttpSetting() {
  const [{ backends = [], credentials = [] }, setHttp] = useHttp();

  const update = (field) => {
    setHttp({
      backends,
      credentials,
      ...field,
    });
  };

  useEffect(() => {
    const img = document.querySelector("#http .addon-logo");
    if (!img) return;
    img.removeAttribute("src");
  }, []);

  return (
    <>
      <Backends
        backends={backends}
        setBackends={(backends) => update({ backends })}
      />
      <Spacer paddingY="calc(8px)" />
      <Credentials
        credentials={credentials}
        setCredentials={(credentials) => update({ credentials })}
      />
    </>
  );
}
