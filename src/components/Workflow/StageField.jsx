const { useMemo } = wp.element;

function useStyle(state, diff) {
  if (!diff) {
    return { color: "inherit", display: "inline-block" };
  }

  return {
    display: "inline-block",
    color: state.enter
      ? "#4ab866"
      : state.exit
        ? "#cc1818"
        : state.mutated || state.touched
          ? "#f0b849"
          : "inherit",
  };
}

export default function WorkflowStageField({
  name,
  schema,
  showDiff,
  enter,
  mutated,
  touched,
  exit,
}) {
  const style = useStyle({ enter, mutated, touched, exit }, showDiff);

  return (
    <div style={style}>
      <strong>{name}</strong>
      <FieldSchema
        data={schema}
        showDiff={showDiff}
        enter={enter}
        exit={exit}
        mutated={mutated}
        touched={touched}
      />
    </div>
  );
}

function FieldSchema({ data, showDiff, enter, exit, mutated, touched }) {
  const content = useMemo(() => {
    switch (data.type) {
      case "object":
        return (
          <ObjectProperties
            data={data.properties}
            showDiff={showDiff}
            enter={enter}
            exit={exit}
            mutated={mutated}
            touched={touched}
          />
        );
      case "array":
        return (
          <ArrayItems
            data={data.items}
            showDiff={showDiff}
            enter={enter}
            exit={exit}
            mutated={mutated}
            touched={touched}
          />
        );
      default:
        return data.type;
    }
  }, [data]);

  return (
    <div
      style={{
        display: "inline",
        marginLeft: "1em",
        paddingLeft: "1em",
        borderLeft: "1px solid",
      }}
    >
      {content}
    </div>
  );
}

function ObjectProperties({ data, showDiff, enter, exit, mutated, touched }) {
  return "object";
}

function ArrayItems({ data, showDiff, enter, exit, mutated, touched }) {
  if (Array.isArray(data)) {
    const types = data.reduce((types, { type }) => {
      if (!types.includes(type)) {
        types.push(type);
      }

      return types;
    }, []);

    if (types.length > 1) {
      return "mixed[]";
    }

    return types[0] + "[]";
  }

  return data.type + "[]";
}
