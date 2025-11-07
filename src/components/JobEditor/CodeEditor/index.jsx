// vendor
import { EditorView, basicSetup } from "codemirror";
import { EditorState } from "@codemirror/state";
import { php } from "@codemirror/lang-php";

const { useEffect, useRef } = wp.element;

export default function JobCodeEditor({ id, doc, update }) {
  const docRef = useRef();
  const editorView = useRef();

  const updateHandler = useRef(
    (() => {
      let timeout;
      return EditorState.changeFilter.of((transaction) => {
        clearTimeout(timeout);
        setTimeout(() => update(transaction.newDoc.toString()), 200);
        return true;
      });
    })()
  ).current;

  useEffect(() => {
    if (!docRef.current) return;

    if (!editorView.current) {
      editorView.current = new EditorView({
        doc,
        parent: docRef.current,
        extensions: [basicSetup, php({ plain: true }), updateHandler],
      });
    }
  }, [doc]);

  return (
    <EditorWrapper id={id}>
      <div ref={docRef}></div>
    </EditorWrapper>
  );
}

function EditorWrapper({ id, children }) {
  const pre = `function forms_bridge_job_${id.replace(/-/g, "_")}($payload, $bridge)
{`;
  const post = `    return $payload;
}`;

  return (
    <>
      <code style={{ display: "block", paddingLeft: "32px" }}>
        <pre
          style={{
            paddingLeft: "5px",
            margin: "2px 0",
            borderLeft: "1px solid #ddd",
            color: "#6c6c6c",
          }}
        >
          {pre}
        </pre>
      </code>
      {children}
      <code style={{ display: "block", paddingLeft: "32px" }}>
        <pre
          style={{
            paddingLeft: "5px",
            margin: "2px 0",
            borderLeft: "1px solid #ddd",
            color: "#6c6c6c",
          }}
        >
          {post}
        </pre>
      </code>
    </>
  );
}
