function useStyle(state, diff) {
  if (!diff) {
    return { color: "inherit", display: "inline-flex" };
  }

  return {
    display: "inline-flex",
    color: state.isNew
      ? "#4ab866"
      : state.exit
        ? "#cc1818"
        : state.mutated
          ? "#f0b849"
          : "inherit",
  };
}

export default function WorkflowStageField({
  name,
  type,
  properties = {},
  items = {},
  diff,
  isNew,
  mutated,
  exit,
}) {
  const style = useStyle({ isNew, mutated, exit }, diff);

  return (
    <div style={style}>
      <strong>{name}</strong>
      <div
        style={{
          marginLeft: "1em",
          paddingLeft: "1em",
          borderLeft: "1px solid",
        }}
      >
        <FieldType type={type} properties={properties} items={items} />
      </div>
    </div>
  );
}

function FieldType({ type, properties, items }) {
  switch (type) {
    case "object":
      return <ObjectProperties properties={properties} />;
    case "array":
      return <ArrayItems items={items} />;
    default:
      return type;
  }
}

function ObjectProperties({ properties }) {
  return "object";
}

function ArrayItems({ items }) {
  return "array";
}
