const { useEffect } = wp.element;
const { TextControl, SelectControl } = wp.components;
const { __ } = wp.i18n;

const OPTIONS = [
  { label: "", value: "" },
  { label: "Basic", value: "Basic" },
  { label: "Token", value: "Token" },
  { label: "Bearer", value: "Bearer" },
];

export default function BackendAuthentication({ data = {}, setData }) {
  useEffect(() => {
    if (!data.type) {
      setData({});
    } else if (data.type === "Bearer") {
      const copy = { ...data };
      delete copy.client_id;
      setData(copy);
    }
  }, [data.type]);

  return (
    <div style={{ display: "flex", gap: "0.5rem" }}>
      <div style={{ width: "250px", marginTop: "calc(8px)" }}>
        <SelectControl
          label={__("Authentication", "forms-bridge")}
          value={data.type || ""}
          onChange={(type) => setData({ ...data, type })}
          options={OPTIONS}
          __next40pxDefaultSize
          __nextHasNoMarginBottom
        />
      </div>
      {data.type && data.type !== "Bearer" && (
        <div style={{ width: "250px", marginTop: "calc(8px)" }}>
          <TextControl
            label={__("Client ID", "forms-bridge")}
            value={data.client_id}
            onChange={(client_id) => setData({ ...data, client_id })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
        </div>
      )}
      {data.type && (
        <div style={{ width: "250px", marginTop: "calc(8px)" }}>
          <TextControl
            label={__("Client secret", "forms-bridge")}
            value={data.client_secret}
            onChange={(client_secret) => setData({ ...data, client_secret })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
        </div>
      )}
    </div>
  );
}
