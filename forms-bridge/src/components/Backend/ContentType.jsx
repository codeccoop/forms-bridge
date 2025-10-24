import FieldWrapper from "../FieldWrapper";

const { useEffect, useMemo } = wp.element;
const { SelectControl } = wp.components;
const { __ } = wp.i18n;

export const WELL_KNOWN_TYPES = {
  "application/json": __("JSON", "forms-bridge"),
  "application/x-www-form-urlencoded": __("URL Encoded", "forms-bridge"),
  "multipart/form-data": __("Binary files", "forms-bridge"),
};

export const HEADER_NAME = "Content-Type";
export const DEFAULT_VALUE = "application/json";

export default function ContentType({ headers, setHeaders }) {
  const value = useMemo(() => {
    return headers.find(({ name }) => name === HEADER_NAME)?.value;
  }, [headers]);

  const setValue = (value) => {
    if (headers[0]?.name !== HEADER_NAME) {
      setHeaders([{ name: HEADER_NAME, value }, ...headers]);
    } else {
      setHeaders([{ name: HEADER_NAME, value }, ...headers.slice(1)]);
    }
  };

  useEffect(() => {
    if (value === undefined && headers.length) {
      setHeaders([{ name: HEADER_NAME, value: DEFAULT_VALUE }, ...headers]);
    }
  }, [value]);

  return (
    <FieldWrapper>
      <SelectControl
        label={__("Content encoding")}
        value={WELL_KNOWN_TYPES[value] ? value : ""}
        onChange={setValue}
        options={Object.keys(WELL_KNOWN_TYPES)
          .map((type) => ({
            label: WELL_KNOWN_TYPES[type],
            value: type,
          }))
          .concat([
            { label: __("Custom encoding", "forms-bridge"), value: "" },
          ])}
        __next40pxDefaultSize
        __nextHasNoMarginBottom
      />
    </FieldWrapper>
  );
}
