import BridgeStep from "../../../../src/components/Templates/Steps/BridgeStep";

const { useMemo, useEffect } = wp.element;
const { __ } = wp.i18n;

const fieldsOrder = ["name"];

const API_FIELDS = ["user_id", "product_id", "tag_ids", "team_id", "list_ids"];

export default function OdooBridgeStep({ fields, data, setData }) {
  const users = data._users || [];
  const products = data._products || [];
  const tags = data._tags || [];
  const teams = data._teams || [];
  const lists = data._lists || [];

  const userOptions = useMemo(() => {
    return users.map(({ id, name }) => ({
      value: id,
      label: name,
    }));
  }, [users]);

  const productOptions = useMemo(() => {
    return products.map(({ id, name }) => ({
      value: id,
      label: name,
    }));
  }, [products]);

  const tagOptions = useMemo(() => {
    return tags.map(({ id, name }) => ({
      value: id,
      label: name,
    }));
  }, [tags]);

  const teamOptions = useMemo(() => {
    return teams.map(({ id, name }) => ({
      value: id,
      label: name,
    }));
  }, [teams]);

  const listOptions = useMemo(() => {
    return lists.map(({ id, name }) => ({
      value: id,
      label: name,
    }));
  }, [lists]);

  const sortedFields = useMemo(
    () =>
      fields.sort((a, b) => {
        if (!fieldsOrder.includes(a.name)) {
          return 1;
        } else if (!fieldsOrder.includes(b.name)) {
          return -1;
        } else {
          fieldsOrder.indexOf(a.name) - fieldsOrder.indexOf(b.name);
        }
      }),
    [fields]
  );

  const standardFields = useMemo(
    () => sortedFields.filter(({ name }) => !API_FIELDS.includes(name)),
    [sortedFields]
  );

  const apiFields = useMemo(
    () =>
      sortedFields
        .filter(({ name }) => API_FIELDS.includes(name))
        .map((field) => {
          if (field.name === "user_id") {
            return {
              ...field,
              type: "options",
              options: userOptions,
            };
          } else if (field.name === "product_id") {
            return {
              ...field,
              type: "options",
              options: productOptions,
            };
          } else if (field.name === "tag_ids") {
            return {
              ...field,
              type: "options",
              options: tagOptions,
              multiple: true,
            };
          } else if (field.name === "team_id") {
            return {
              ...field,
              type: "options",
              options: teamOptions,
            };
          } else if (field.name === "list_ids") {
            return {
              ...field,
              type: "options",
              options: listOptions,
              multiple: true,
            };
          }
        }),
    [sortedFields]
  );

  useEffect(() => {
    const defaults = {};

    if (productOptions.length > 0 && !data.product_id) {
      defaults.product_id = productOptions[0].value;
    }

    if (userOptions.length > 0 && !data.user_id) {
      defaults.user_id = userOptions[0].value;
    }

    if (teamOptions.length > 0 && !data.team_id) {
      defaults.team_id = teamOptions[0].value;
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
