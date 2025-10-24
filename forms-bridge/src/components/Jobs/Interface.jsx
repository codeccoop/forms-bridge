import PayloadField from "../Workflow/PayloadField";

const { useMemo } = wp.element;
const { __experimentalItemGroup: ItemGroup, __experimentalItem: Item } =
  wp.components;
const { __ } = wp.i18n;

export default function JobInterface({ fields = [] }) {
  const requiredFields = useMemo(() => {
    return fields.map((field) => {
      let name = field.name;
      if (field.required) {
        name += "∗";
      } else if (field.forward) {
        name += "?";
      }

      return { ...field, name, mutated: field.touch };
    });
  }, [fields]);

  if (!requiredFields.length) {
    return (
      <p style={{ lineHeight: "2.65" }}>
        {__("Empty interface", "forms-bridge")}
      </p>
    );
  }

  return (
    <ItemGroup size="large" isSeparated>
      {requiredFields.map((field, i) => (
        <Item key={field.name + i}>
          <PayloadField {...field} showDiff />
        </Item>
      ))}
    </ItemGroup>
  );
}
