// source
import BridgeStep from "../../../../src/components/Templates/Steps/BridgeStep";

const { useMemo, useEffect } = wp.element;
const { __ } = wp.i18n;

const API_FIELDS = ["userownerid", "fk_product"];

export default function DolibarrBridgeStep({ fields, data, setData }) {
  const users = useMemo(() => data._users || [], [data._users]);
  const products = useMemo(() => data._products || [], [data._products]);

  const userOptions = useMemo(
    () =>
      users.map(({ id, email }) => ({
        value: id,
        label: email,
      })),
    [users]
  );

  const productOptions = useMemo(
    () =>
      products.map(({ id, label }) => ({
        value: id,
        label: label,
      })),
    [products]
  );

  const standardFields = useMemo(
    () => fields.filter(({ name }) => !API_FIELDS.includes(name)),
    [fields]
  );

  const apiFields = useMemo(() => {
    return fields
      .filter(({ name }) => API_FIELDS.includes(name))
      .map((field) => {
        if (field.name === "userownerid") {
          return {
            ...field,
            type: "options",
            options: userOptions,
          };
        } else if (field.name === "fk_product") {
          return {
            ...field,
            type: "options",
            options: productOptions,
          };
        }
      });
  }, [fields, userOptions, productOptions]);

  useEffect(() => {
    const defaults = {};

    if (productOptions.length > 0 && !data.fk_product) {
      defaults.fk_product = productOptions[0].value;
    }

    if (userOptions.length > 0 && !data.userownerid) {
      defaults.userownerid = userOptions[0].value;
    }

    setData(defaults);
  }, [productOptions, userOptions]);

  return (
    <BridgeStep
      fields={standardFields.concat(apiFields)}
      data={data}
      setData={setData}
    />
  );
}
