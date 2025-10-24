const { useMemo } = wp.element;

export default function JobSnippet({ id, snippet }) {
  /* global hljs */

  const highlighted = useMemo(() => {
    const code = `function forms_bridge_job_${id.replace(/-/g, "_")}($payload, $bridge)
{
${snippet.replace(/(\n|\t)+$/, "")}

    return $payload;
}`;

    if (!hljs) {
      return "<p style='margin:0;color:#cc1818'><b>ERROR</b>: Highlight.js is unavailable</p>";
    }

    return hljs.highlight(code, { language: "php" })?.value || "";
  }, [id, snippet]);

  return (
    <code style={{ padding: "calc(8px)", background: "transparent" }}>
      <pre
        style={{ margin: 0 }}
        dangerouslySetInnerHTML={{
          __html: highlighted,
        }}
      ></pre>
    </code>
  );
}
