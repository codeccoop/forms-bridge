import JsonFinger from "../../lib/JsonFinger";
import {
  applyMappers,
  castValue,
  checkType,
  fieldsToPayload,
  payloadToFields,
} from "../../lib/payload";
import PayloadField from "./PayloadField";

const { useMemo } = wp.element;
const { __experimentalItemGroup: ItemGroup, __experimentalItem: Item } =
  wp.components;

export default function StagePayload({
  fields,
  mappers,
  showMutations = false,
  showDiff = false,
  diff,
}) {
  const payloadDiff = useMemo(() => {
    if (!showMutations) return diff;

    const payloadDiff = Object.fromEntries(
      Object.entries(diff).map(([key, set]) => [key, new Set(set)])
    );

    const nameMutations = {};

    mappers
      .map((mapper) => {
        const [from] = JsonFinger.parse(mapper.from);
        const [to] = JsonFinger.parse(mapper.to);

        if (from !== to) {
          nameMutations[to] = from;
        }

        return mapper;
      })
      .reverse()
      .forEach((mapper) => {
        const [from] = JsonFinger.parse(mapper.from);
        const [to] = JsonFinger.parse(mapper.to);

        if (to !== from) {
          payloadDiff.enter.add(to);

          if (payloadDiff.enter.has(from)) {
            payloadDiff.enter.delete(from);
          } else {
            payloadDiff.exit.add(from);
          }
        } else {
          if (payloadDiff.mutated.has(from)) {
            payloadDiff.mutated.delete(from);
            payloadDiff.mutated.add(to);
          } else {
            let name = from;
            while (nameMutations[name] && nameMutations[name] !== name) {
              name = nameMutations[name];
            }

            const field = fields.find((field) => field.name === name);
            if (!field) {
              return;
            }

            if (!checkType(field.type, castValue(field.type, mapper))) {
              payloadDiff.mutated.add(to);
            }
          }
        }
      });

    return payloadDiff;
  }, [fields, diff, showMutations, mappers]);

  const payloadFields = useMemo(() => {
    let output;

    if (!showMutations) {
      output = fields.map((field) => ({ ...field }));
    } else {
      output = payloadToFields(applyMappers(fieldsToPayload(fields), mappers));
    }

    if (showDiff) {
      output.forEach((field) => {
        field.enter = payloadDiff.enter.has(field.name);
        field.mutated = payloadDiff.mutated.has(field.name);
        field.exit = false;
      });

      Array.from(payloadDiff.exit).forEach((name) => {
        output.push({
          name,
          schema: { type: "null" },
          enter: false,
          mutated: false,
          exit: true,
        });
      });
    }

    return output;
  }, [fields, mappers, showMutations, showDiff, payloadDiff]);

  return (
    <ItemGroup size="large" isSeparated>
      {payloadFields.map((field, i) => (
        <Item key={field.name + i}>
          <PayloadField {...field} showDiff={showDiff} />
        </Item>
      ))}
    </ItemGroup>
  );
}
