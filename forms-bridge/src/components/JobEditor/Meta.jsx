const { TextControl, TextareaControl } = wp.components;
const { __ } = wp.i18n;

export default function JobMeta({ data, setData }) {
  return (
    <div
      style={{
        display: "flex",
        flexDirection: "column",
        gap: "1rem",
        paddingBottom: "2rem",
      }}
    >
      <TextControl
        label={__("Title", "forms-bridge")}
        value={data.title}
        onChange={(title) => setData({ title })}
        __next40pxDefaultSize
        __nextHasNoMarginBottom
      />
      <TextareaControl
        label={__("Description", "forms-bridge")}
        value={data.description}
        onChange={(description) => setData({ description })}
        __nextHasNoMarginBottom
      />
    </div>
  );
}
