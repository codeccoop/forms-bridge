// source
import { useForms } from "../../providers/Forms";
import { useGeneral } from "../../providers/Settings";
import useBridgeNames from "../../hooks/useBridgeNames";
import BridgePipes from "../BridgePipes";
import NewBridge from "./NewBridge";

const {
  TextControl,
  SelectControl,
  Button,
  __experimentalSpacer: Spacer,
} = wp.components;
const { useState, useRef, useEffect, useMemo } = wp.element;
const { __ } = wp.i18n;

export default function Bridge({
  data,
  update,
  remove,
  schema = ["name", "backend", "form_id"],
  template = ({ add, schema }) => <NewBridge add={add} schema={schema} />,
  children = () => {},
}) {
  if (data.name === "add") return template({ add: update, schema });

  const [{ backends }] = useGeneral();
  const backendOptions = [{ label: "", value: "" }].concat(
    backends.map(({ name }) => ({
      label: name,
      value: name,
    }))
  );

  const forms = useForms();
  const formOptions = [{ label: "", value: "" }].concat(
    forms.map(({ _id, title }) => ({
      label: title,
      value: _id,
    }))
  );

  const form = useMemo(() => {
    return forms.find((form) => form._id == data.form_id);
  }, [data.form_id]);

  const [name, setName] = useState(data.name);
  const initialName = useRef(data.name);

  const bridgeNames = useBridgeNames();
  const [nameConflict, setNameConflict] = useState(false);
  const handleSetName = (name) => {
    setNameConflict(
      name !== initialName.current && bridgeNames.has(name.trim())
    );
    setName(name);
  };

  const timeout = useRef();
  useEffect(() => {
    clearTimeout(timeout.current);
    if (!name || nameConflict) return;
    timeout.current = setTimeout(() => {
      if (bridgeNames.has(name.trim())) return;
      update({ ...data, name: name.trim() });
    }, 500);
  }, [name]);

  useEffect(() => setName(data.name), [data.name]);

  return (
    <div
      style={{
        padding: "calc(24px) calc(32px)",
        width: "calc(100% - 64px)",
        backgroundColor: "rgb(245, 245, 245)",
      }}
    >
      <div
        style={{
          display: "flex",
          gap: "1em",
          flexWrap: "wrap",
        }}
      >
        <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
          <TextControl
            label={__("Name", "forms-bridge")}
            help={
              nameConflict
                ? __("This name is already in use", "forms-bridge")
                : ""
            }
            value={name}
            onChange={handleSetName}
            __nextHasNoMarginBottom
            __next40pxDefaultSize
          />
        </div>
        {schema.includes("backend") && (
          <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
            <SelectControl
              label={__("Backend", "forms-bridge")}
              value={data.backend}
              onChange={(backend) => update({ ...data, backend })}
              options={backendOptions}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </div>
        )}
        {schema.includes("form_id") && (
          <div style={{ flex: 1, minWidth: "150px", maxWidth: "250px" }}>
            <SelectControl
              label={__("Form", "forms-bridge")}
              value={data.form_id}
              onChange={(form_id) => update({ ...data, form_id })}
              options={formOptions}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </div>
        )}
        {children({ data, update })}
      </div>
      <Spacer paddingY="calc(8px)" />
      <div
        style={{
          display: "flex",
          gap: "1em",
          flexWrap: "wrap",
        }}
      >
        <BridgePipes
          form={form}
          pipes={data.pipes}
          setPipes={(pipes) => update({ ...data, pipes })}
        />
        <Button
          isDestructive
          variant="primary"
          onClick={() => remove(data)}
          style={{ width: "150px", justifyContent: "center" }}
          __next40pxDefaultSize
        >
          {__("Remove", "forms-bridge")}
        </Button>
      </div>
    </div>
  );
}
