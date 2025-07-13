const {
  TextControl,
  SelectControl,
  Button,
  __experimentalSpacer: Spacer,
} = wp.components;
const { useEffect, useMemo } = wp.element;
const { __ } = wp.i18n;

const WELL_KNOWN_CONTENT_TYPES = {
  "application/json": "JSON",
  "application/x-www-form-urlencoded": "URL Encoded",
  "multipart/form-data": "Binary files",
};

function ContentTypeHeader({ setValue, value }) {
  return (
    <div style={{ width: "250px", marginTop: "calc(8px)" }}>
      <SelectControl
        label={__("Content encoding")}
        value={WELL_KNOWN_CONTENT_TYPES[value] ? value : ""}
        onChange={setValue}
        options={Object.keys(WELL_KNOWN_CONTENT_TYPES)
          .map((type) => ({
            label: WELL_KNOWN_CONTENT_TYPES[type],
            value: type,
          }))
          .concat([
            { label: __("Custom encoding", "forms-bridge"), value: "" },
          ])}
        __next40pxDefaultSize
        __nextHasNoMarginBottom
      />
    </div>
  );
}

export default function BackendHeaders({ headers, setHeaders }) {
  const contentType =
    headers.find((header) => header.name === "Content-Type")?.value || "";

  const setContentType = (type) => {
    const index = headers.findIndex((header) => header.name === "Content-Type");
    if (index === -1) {
      addHeader(0, "Content-Type", type);
    } else {
      setHeader("value", index, type);
    }
  };

  const setHeader = (attr, index, value) => {
    const newHeaders = headers.map((header, i) => {
      if (index === i) header[attr] = value;
      return { ...header };
    });

    setHeaders(newHeaders);
  };

  const addHeader = (index, name = "Accept", value = "application/json") => {
    const newHeaders = headers
      .slice(0, index)
      .concat([{ name, value }])
      .concat(headers.slice(index, headers.length));

    setHeaders(newHeaders);
  };

  const dropHeader = (index) => {
    const newHeaders = headers.slice(0, index).concat(headers.slice(index + 1));
    setHeaders(newHeaders);
  };

  useEffect(() => {
    if (!(headers.length && headers.find((h) => h.name === "Content-Type")))
      addHeader(0, "Content-Type", "application/json");
  }, [headers]);

  const sortedHeaders = useMemo(
    () =>
      headers.sort((h1, h2) => {
        if (h1.name === "Content-Type") return -1;
        if (h2.name === "Content-Type") return 1;
        return 0;
      }),
    [headers]
  );

  return (
    <>
      <ContentTypeHeader value={contentType} setValue={setContentType} />
      <Spacer paddingY="calc(4px)" />
      <div className="components-base-control__label">
        <label
          className="components-base-control__label"
          style={{
            fontSize: "11px",
            textTransform: "uppercase",
            fontWeight: 500,
            marginBottom: "calc(8px)",
          }}
        >
          {__("HTTP Headers", "forms-bridge")}
        </label>
        <table
          style={{
            width: "calc(100% + 10px)",
            borderSpacing: "5px",
            margin: "0 -5px",
          }}
        >
          <tbody>
            {sortedHeaders.map(({ name, value }, i) => (
              <tr key={i}>
                <td>
                  <TextControl
                    disabled={name === "Content-Type" && i === 0}
                    placeholder={__("Header-Name", "forms-bridge")}
                    value={name}
                    onChange={(value) => setHeader("name", i, value)}
                    __nextHasNoMarginBottom
                    __next40pxDefaultSize
                  />
                </td>
                <td>
                  <TextControl
                    disabled={
                      name === "Content-Type" &&
                      WELL_KNOWN_CONTENT_TYPES[value] &&
                      i === 0
                    }
                    placeholder={__("Value", "forms-bridge")}
                    value={value}
                    onChange={(value) => setHeader("value", i, value)}
                    __nextHasNoMarginBottom
                    __next40pxDefaultSize
                  />
                </td>
                <td>
                  <div
                    style={{
                      display: "flex",
                      marginLeft: "0.45em",
                      gap: "0.45em",
                    }}
                  >
                    <Button
                      size="compact"
                      variant="secondary"
                      disabled={!name || !value}
                      onClick={() => addHeader(i + 1)}
                      style={{
                        width: "40px",
                        height: "40px",
                        justifyContent: "center",
                      }}
                      __next40pxDefaultSize
                    >
                      +
                    </Button>
                    <Button
                      disabled={name === "Content-Type" && i === 0}
                      variant="secondary"
                      onClick={() => dropHeader(i)}
                      style={{
                        width: "40px",
                        height: "40px",
                        justifyContent: "center",
                      }}
                      isDestructive
                      __next40pxDefaultSize
                    >
                      -
                    </Button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </>
  );
}
