import BridgeStep from "../../../../src/components/Templates/Steps/BridgeStep";

const { useMemo, useEffect } = wp.element;

const API_FIELDS = ["funnelId", "sku", "serviceId"];

export default function HoldedBridgeStep({ fields, data, setData }) {
  const funnels = useMemo(() => data._funnels || [], [data._funnels]);
  const products = useMemo(() => data._products || [], [data._products]);
  const services = useMemo(() => data._services || [], [data._services]);

  const funnelOptions = useMemo(() => {
    return funnels.map(({ id, name }) => ({
      value: id,
      label: name,
    }));
  }, [funnels]);

  const productOptions = useMemo(() => {
    return products.map(({ sku, name }) => ({
      value: sku,
      label: name,
    }));
  }, [products]);

  const serviceOptions = useMemo(() => {
    return services.map(({ id, name }) => ({
      value: id,
      label: name,
    }));
  }, [services]);

  const standardFields = useMemo(
    () => fields.filter(({ name }) => !API_FIELDS.includes(name)),
    [fields]
  );

  const apiFields = useMemo(() => {
    return fields
      .filter(({ name }) => API_FIELDS.includes(name))
      .map((field) => {
        if (field.name === "funnelId") {
          return {
            ...field,
            type: "options",
            options: funnelOptions,
          };
        } else if (field.name === "sku") {
          return {
            ...field,
            type: "options",
            options: productOptions,
          };
        } else if (field.name === "serviceId") {
          return {
            ...field,
            type: "options",
            options: serviceOptions,
          };
        }
      });
  }, [fields, funnelOptions, productOptions]);

  useEffect(() => {
    const defaults = {};

    if (funnelOptions.length > 0 && !data.funnelId) {
      defaults.funnelId = funnelOptions[0].value;
    }

    if (productOptions.length > 0 && !data.sku) {
      defaults.sku = productOptions[0].value;
    }

    if (serviceOptions.length > 0 && !data.serviceId) {
      defaults.serviceId = serviceOptions[0].value;
    }
    setData(defaults);
  }, [productOptions, funnelOptions]);

  return (
    <BridgeStep
      fields={standardFields.concat(apiFields)}
      data={data}
      setData={setData}
    />
  );
}
