// vendor
import React from "react";
import { TextControl, SelectControl } from "@wordpress/components";

// source
import FormHook from "../../../../src/components/FormHooks/FormHook";
import NewOdooFormHook from "./NewFormHook";
import useOdooApi from "../hooks/useOdooSetting";

let focus;
export default function OdooFormHook({ data, update, remove }) {
  const __ = wp.i18n.__;

  const [{ databases }] = useOdooApi();
  const dbOptions = [{ label: "", value: "" }].concat(
    databases.map(({ name }) => ({
      label: name,
      value: name,
    }))
  );

  return (
    <FormHook
      data={data}
      update={update}
      remove={remove}
      template={({ add, schema }) => (
        <NewOdooFormHook add={add} schema={schema} />
      )}
      schema={["name", "form_id", "model", "database"]}
    >
      {({ data, update }) => (
        <>
          <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
            <TextControl
              label={__("Model", "forms-bridge")}
              value={data.model}
              onChange={(model) => update({ ...data, model })}
              __nextHasNoMarginBottom
            />
          </div>
          <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
            <SelectControl
              label={__("Database", "forms-bridge")}
              value={data.database}
              onChange={(database) => update({ ...data, database })}
              options={dbOptions}
              __nextHasNoMarginBottom
            />
          </div>
        </>
      )}
    </FormHook>
  );

  // if (data.name === "add") return <NewFormHook add={update} />;

  // const __ = wp.i18n.__;
  // const backends = useBackends();
  // const backendOptions = [{ label: "", value: "" }].concat(
  //   backends.map(({ name }) => ({
  //     label: name,
  //     value: name,
  //   }))
  // );
  // const forms = useForms();
  // const formOptions = [{ label: "", value: "" }].concat(
  //   forms.map(({ id, title }) => ({
  //     label: title,
  //     value: id,
  //   }))
  // );

  // const [name, setName] = useState(data.name);
  // const initialName = useRef(data.name);
  // const nameInput = useRef();

  // const formHooks = useFormHooks();
  // const hookNames = useHookNames(formHooks);
  // const [nameConflict, setNameConflict] = useState(false);
  // const handleSetName = (name) => {
  //   setNameConflict(name !== initialName.current && hookNames.has(name.trim()));
  //   setName(name);
  // };

  // useEffect(() => {
  //   if (focus) {
  //     nameInput.current.focus();
  //   }
  // }, []);

  // const timeout = useRef();
  // useEffect(() => {
  //   clearTimeout(timeout.current);
  //   if (!name || nameConflict) return;
  //   timeout.current = setTimeout(() => {
  //     if (hookNames.has(name.trim())) return;
  //     update({ ...data, name: name.trim() });
  //   }, 500);
  // }, [name]);

  // useEffect(() => setName(data.name), [data.name]);

  // return (
  //   <div
  //     style={{
  //       padding: "calc(24px) calc(32px)",
  //       width: "calc(100% - 64px)",
  //       backgroundColor: "rgb(245, 245, 245)",
  //     }}
  //   >
  //     <div
  //       style={{
  //         display: "flex",
  //         gap: "1em",
  //         flexWrap: "wrap",
  //       }}
  //     >
  //       <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
  //         <TextControl
  //           ref={nameInput}
  //           label={__("Name", "forms-bridge")}
  //           help={
  //             nameConflict
  //               ? __("This name is already in use", "forms-bridge")
  //               : ""
  //           }
  //           value={name}
  //           onChange={handleSetName}
  //           onFocus={() => (focus = true)}
  //           onBlur={() => (focus = false)}
  //           __nextHasNoMarginBottom
  //         />
  //       </div>
  //       <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
  //         <SelectControl
  //           label={__("Backend", "forms-bridge")}
  //           value={data.backend}
  //           onChange={(backend) => update({ ...data, backend })}
  //           options={backendOptions}
  //           __nextHasNoMarginBottom
  //         />
  //       </div>
  //       <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
  //         <TextControl
  //           label={__("Model", "forms-bridge")}
  //           value={data.model}
  //           onChange={(model) => update({ ...data, model })}
  //           __nextHasNoMarginBottom
  //         />
  //       </div>
  //       <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
  //         <SelectControl
  //           label={__("Form", "forms-bridge")}
  //           value={data.form_id}
  //           onChange={(form_id) => update({ ...data, form_id })}
  //           options={formOptions}
  //           __nextHasNoMarginBottom
  //         />
  //       </div>
  //     </div>
  //     <Spacer paddingY="calc(8px)" />
  //     <div
  //       style={{
  //         display: "flex",
  //         gap: "1em",
  //         flexWrap: "wrap",
  //       }}
  //     >
  //       <div>
  //         <label
  //           style={{
  //             display: "block",
  //             fontWeight: 500,
  //             textTransform: "uppercase",
  //             fontSize: "11px",
  //             marginBottom: "calc(4px)",
  //             maxWidth: "unset",
  //           }}
  //         >
  //           {__("Edit pipes", "forms-bridge")}
  //         </label>
  //         <FormPipes
  //           formId={data.form_id}
  //           pipes={data.pipes || []}
  //           setPipes={(pipes) => update({ ...data, pipes })}
  //         />
  //       </div>
  //       <div>
  //         <label
  //           style={{
  //             display: "block",
  //             fontWeight: 500,
  //             textTransform: "uppercase",
  //             fontSize: "11px",
  //             margin: 0,
  //             marginBottom: "calc(4px)",
  //             maxWidth: "100%",
  //           }}
  //         >
  //           {__("Remove form", "forms-bridge")}
  //         </label>
  //         <Button
  //           isDestructive
  //           variant="primary"
  //           onClick={() => remove(data)}
  //           style={{ width: "130px", justifyContent: "center", height: "32px" }}
  //         >
  //           {__("Remove", "forms-bridge")}
  //         </Button>
  //       </div>
  //     </div>
  //   </div>
  // );
}
