const { useEffect, useState, useRef } = wp.element;

export default function useTab() {
  const root = useRef(document.querySelector("#forms-bridge")).current;

  const [tab, setTab] = useState(() => {
    const query = new URLSearchParams(window.location.search);
    return query.get("tab") || "general";
  });

  useEffect(() => {
    const onTab = ({ detail }) => {
      tab !== detail && setTab(detail);
    };

    root.addEventListener("tab", onTab);
    return () => root.removeEventListener("tab", onTab);
  }, [tab]);

  useEffect(() => {
    const from = new URLSearchParams(window.location.search);
    if (from.get("tab") === tab) return;

    const to = new URLSearchParams(from.toString());
    to.set("tab", tab);

    window.history.replaceState(
      { from: `${window.location.pathname}?${from.toString()}` },
      "",
      `${window.location.pathname}?${to.toString()}`
    );

    root.dispatchEvent(new CustomEvent("tab", { detail: tab }));
  }, [tab]);

  return [tab, setTab];
}
