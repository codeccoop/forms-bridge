const { useRef } = wp.element;
const { TextControl, TextareaControl } = wp.components;
const { __ } = wp.i18n;

export default function JobMeta({ data, setData }) {
  const setName = useRef((name) => {
    setData({
      name: name
        .toLowerCase()
        .replace(/\s+/g, "-")
        .replace(/[^[0-9a-z-_]/g, "")
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, ""),
    });
  }).current;

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
        label={__("Name", "forms-bridge")}
        help={__("Unique and interna name of the job", "forms-bridge")}
        value={data.name}
        onChange={setName}
        __next40pxDefaultSize
        __nextHasNoMarginBottom
      />
      <TextControl
        label={__("Title", "forms-bridge")}
        help={__("Public title of the job", "forms-bridge")}
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
