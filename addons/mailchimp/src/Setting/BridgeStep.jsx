import BridgeStep from "../../../../src/components/Templates/Steps/BridgeStep";

const { useMemo, useEffect } = wp.element;

const API_FIELDS = ["list_id"];

export default function MailchimpBridgeStep({ fields, data, setData }) {
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

  const apiFields = useMemo(
    () =>
      fields
        .filter(({ name }) => API_FIELDS.includes(name))
        .map((field) => {
          if (field.name === "list_id") {
            return {
              ...field,
              type: "options",
              options: listOptions,
            };
          }
        }),
    [fields]
  );

  useEffect(() => {
    const defaults = {};

    if (listOptions.length > 0 && !data.list_id) {
      defaults.list_id = listOptions[0].value;
    }

    setData(defaults);
  }, [listOptions]);

  return (
    <BridgeStep
      fields={standardFields.concat(apiFields)}
      data={data}
      setData={setData}
    />
  );
}
