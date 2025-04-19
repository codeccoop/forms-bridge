import BridgeStep from "../../../../src/components/Templates/Steps/BridgeStep";

const { useMemo, useEffect } = wp.element;
const { __ } = wp.i18n;

const API_FIELDS = [
  "listIds",
  "includeListIds",
  "product",
  "products",
  "pipeline",
  "templateId",
];

export default function BrevoBridgeStep({ fields, data, setData }) {
  const lists = useMemo(() => data._lists || [], [data._lists]);
  const pipelines = useMemo(() => data._pipelines || [], [data._pipelines]);
  const products = useMemo(() => data._products || [], [data._products]);
  const templates = useMemo(() => data._templates || [], [data._templates]);

  const listOptions = useMemo(
    () =>
      lists.map(({ id, name }) => ({
        value: id,
        label: name,
      })),
    [lists]
  );

  const productOptions = useMemo(
    () =>
      products.map(({ id, name }) => ({
        value: id,
        label: name,
      })),
    [products]
  );

  const templateOptions = useMemo(
    () =>
      templates.map(({ id, name }) => ({
        value: id,
        label: name,
      })),
    [templates]
  );

  const pipelineOptions = useMemo(
    () =>
      pipelines.map(({ pipeline, pipeline_name }) => ({
        value: pipeline,
        label: pipeline_name,
      })),
    [pipelines]
  );

  const standardFields = useMemo(
    () => fields.filter(({ name }) => !API_FIELDS.includes(name)),
    [fields]
  );

  const apiFields = useMemo(() => {
    return fields
      .filter(({ name }) => API_FIELDS.includes(name))
      .map((field) => {
        if (field.name === "listIds" || field.name === "includeListIds") {
          return {
            ...field,
            type: "options",
            options: listOptions,
            multiple: true,
          };
        } else if (field.name === "product") {
          return {
            ...field,
            type: "options",
            options: productOptions,
          };
        } else if (field.name === "pipeline") {
          return {
            ...field,
            type: "options",
            options: pipelineOptions,
          };
        } else if (field.name === "templateId") {
          return {
            ...field,
            type: "options",
            options: templateOptions,
          };
        }
      });
  }, [fields, listOptions, productOptions, pipelineOptions, templateOptions]);

  useEffect(() => {
    const defaults = {};

    if (productOptions.length > 0 && !data.productId) {
      defaults.product = productOptions[0].value;
    }

    if (templateOptions.length > 0 && !data.templateId) {
      defaults.templateId = templateOptions[0].value;
    }

    if (pipelineOptions.length > 0 && !data.pipeline) {
      defaults.pipeline = pipelines[0].value;
    }

    setData(defaults);
  }, [productOptions, templateOptions, pipelineOptions]);

  return (
    <BridgeStep
      fields={standardFields.concat(apiFields)}
      data={data}
      setData={setData}
    />
  );
}
