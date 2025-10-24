import { Prec } from "@codemirror/state";
import { keymap } from "@codemirror/view";

export function ctrlS(handler) {
  return Prec.high(
    keymap.of([
      {
        key: "Ctrl+s",
        run: (view) => {
          handler(view.state.doc.toString());
          return true;
        },
      },
    ])
  );
}
