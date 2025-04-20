const { useEffect, useState, useRef } = wp.element;

export default function useCurrentApi() {
  const [api, setApi] = useState(null);

  const onApi = useRef((api) => setApi(api)).current;

  useEffect(() => {
    const tab = new URLSearchParams(window.location.search).get("tab");
    if (tab !== "general") setApi(tab);

    wpfb.on("api", onApi);

    return () => {
      wpfb.off("api", onApi);
    };
  }, []);

  return api;
}
