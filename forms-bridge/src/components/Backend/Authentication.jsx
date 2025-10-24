import FieldWrapper from "../FieldWrapper";

const { TextControl, SelectControl } = wp.components;
const { __ } = wp.i18n;

const OPTIONS = [
  { label: "", value: "" },
  { label: "Basic", value: "Basic" },
  { label: "Token", value: "Token" },
  { label: "Bearer", value: "Bearer" },
];

export default function BackendAuthentication({ data = {}, setData }) {
  return (
    <>
      <FieldWrapper>
        <SelectControl
          label={__("Authentication", "forms-bridge")}
          value={data.schema || ""}
          onChange={(schema) => setData({ ...data, schema })}
          options={OPTIONS}
          __next40pxDefaultSize
          __nextHasNoMarginBottom
        />
      </FieldWrapper>
      {data.schema && data.schema !== "Bearer" && (
        <FieldWrapper>
          <TextControl
            label={__("Client ID", "forms-bridge")}
            help={!data.client_id && __("Required", "forms-bridge")}
            value={data.client_id}
            onChange={(client_id) => setData({ ...data, client_id })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
        </FieldWrapper>
      )}
      {data.schema && (
        <FieldWrapper>
          <TextControl
            label={__("Client secret", "forms-bridge")}
            help={!data.client_secret && __("Required", "forms-bridge")}
            value={data.client_secret}
            onChange={(client_secret) => setData({ ...data, client_secret })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
        </FieldWrapper>
      )}
    </>
  );
}
