import BridgeStep from "../../../../src/components/Templates/Steps/BridgeStep";

const { useMemo } = wp.element;
const { __ } = wp.i18n;

const API_FIELDS = ["lists"];

export default function ListmonkBridgeStep({ fields, data, setData }) {
  const lists = useMemo(() => data._lists || [], [data._lists]);

  const listOptions = useMemo(
    () =>
      lists.map(({ id, name }) => ({
        value: id,
        label: name,
      })),
    [lists]
  );

  const standardFields = useMemo(
    () => fields.filter(({ name }) => !API_FIELDS.includes(name)),
    [fields]
  );

  const apiFields = useMemo(() => {
    return fields
      .filter(({ name }) => API_FIELDS.includes(name))
      .map((field) => {
        if (field.name === "lists") {
          return {
            ...field,
            type: "options",
            options: listOptions,
            multiple: true,
          };
        }
      });
  }, [fields, listOptions]);

  return (
    <BridgeStep
      fields={standardFields.concat(apiFields)}
      data={data}
      setData={setData}
    />
  );
}
