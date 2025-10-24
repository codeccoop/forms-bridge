const { useMemo } = wp.element;

function useStyle(state, diff, simple) {
  if (!diff) {
    return { color: "inherit", display: simple ? "flex" : "block" };
  }

  return {
    display: simple ? "flex" : "block",
    color: state.enter
      ? "#4ab866"
      : state.exit
        ? "#cc1818"
        : state.mutated
          ? "#f0b849"
          : "inherit",
  };
}

export default function PayloadField({
  name,
  schema,
  showDiff,
  enter,
  mutated,
  exit,
  simple = false,
}) {
  const style = useStyle({ enter, mutated, exit }, showDiff, simple);

  return (
    <div style={style}>
      <span>
        <span
          style={
            simple
              ? {
                  paddingRight: "0.5em",
                  margin: "1px 0.5em 1px 0",
                  borderRight: "1px solid",
                }
              : {}
          }
        >
          <strong>{name}</strong>
        </span>
      </span>
      <FieldSchema
        data={schema}
        showDiff={showDiff}
        enter={enter}
        exit={exit}
        mutated={mutated}
        simple={simple}
      />
    </div>
  );
}

function FieldSchema({ data, showDiff, enter, exit, mutated, simple }) {
  return useMemo(() => {
    switch (data.type) {
      case "object":
        return (
          <ObjectProperties
            properties={data.properties}
            showDiff={showDiff}
            enter={enter}
            exit={exit}
            mutated={mutated}
            simple={simple}
          />
        );
      case "array":
        return (
          <ArrayItems
            items={data.items}
            showDiff={showDiff}
            enter={enter}
            exit={exit}
            mutated={mutated}
            simple={simple}
          />
        );
      default:
        return <div>{data.type}</div>;
    }
  }, [data]);
}

function ObjectProperties({
  properties,
  showDiff,
  enter,
  exit,
  mutated,
  arrayItem = 0,
  simple = false,
}) {
  const type = arrayItem
    ? "object" +
      Array.apply(null, Array(arrayItem))
        .map(() => "[]")
        .join("")
    : "object";

  if (simple) return <div>{type}</div>;

  return (
    <>
      <div>{type}</div>
      <ul
        style={{
          paddingLeft: "25px",
          marginBottom: 0,
          marginTop: "5px",
          marginLeft: "3px",
          paddingTop: "5px",
          borderLeft: "1px dashed",
        }}
      >
        {Object.keys(properties).map((prop) => (
          <li key={prop}>
            <PayloadField
              name={prop}
              schema={properties[prop]}
              showDiff={showDiff}
              enter={enter}
              mutated={mutated}
              exit={exit}
            />
          </li>
        ))}
      </ul>
    </>
  );
}

function ArrayItems({
  items,
  showDiff,
  enter,
  exit,
  mutated,
  simple,
  arrayItem = 0,
}) {
  if (Array.isArray(items)) {
    const types = items.reduce((types, { type }) => {
      if (!types.includes(type)) {
        types.push(type);
      }

      return types;
    }, []);

    const type = types.length > 1 ? "mixed" : types[0];
    return (
      <ArrayItems
        items={{ ...items[0], type }}
        showDiff={showDiff}
        enter={enter}
        exit={exit}
        mutated={mutated}
        simple={simple}
      />
    );
  }

  if (items.type === "object" && !simple) {
    return (
      <ObjectProperties
        properties={items.properties || {}}
        showDiff={showDiff}
        enter={enter}
        exit={exit}
        mutated={mutated}
        arrayItem={arrayItem + 1}
      />
    );
  }

  if (items.type === "array") {
    return (
      <ArrayItems
        items={items.items}
        showDiff={showDiff}
        enter={enter}
        exit={exit}
        mutated={mutated}
        arrayItem={arrayItem + 1}
        simple={simple}
      />
    );
  }

  const type =
    items.type +
    Array.apply(null, Array(arrayItem + 1))
      .map(() => "[]")
      .join("");

  return <div>{type}</div>;
}
