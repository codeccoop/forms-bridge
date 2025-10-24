import {
  WELL_KNOWN_TYPES,
  HEADER_NAME as CONTENT_TYPE_NAME,
  DEFAULT_VALUE as DEFAUTL_CONTENT_TYPE,
} from "./ContentType";

const { TextControl, Button } = wp.components;
const { useEffect, useMemo } = wp.element;
const { __ } = wp.i18n;

export default function BackendHeaders({ headers, setHeaders }) {
  const setHeader = (attr, index, value) => {
    const newHeaders = headers.map((header, i) => {
      if (index === i) header[attr] = value;
      return { ...header };
    });

    setHeaders(newHeaders);
  };

  const addHeader = (index, name = "Accept", value = DEFAUTL_CONTENT_TYPE) => {
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
    if (!(headers.length && headers.find((h) => h.name === CONTENT_TYPE_NAME)))
      addHeader(0, CONTENT_TYPE_NAME, DEFAUTL_CONTENT_TYPE);
  }, [headers]);

  const sortedHeaders = useMemo(
    () =>
      headers.sort((h1, h2) => {
        if (h1.name === CONTENT_TYPE_NAME) return -1;
        if (h2.name === CONTENT_TYPE_NAME) return 1;
        return 0;
      }),
    [headers]
  );

  return (
    <>
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
            maxWidth: "900px",
            borderSpacing: "5px",
            margin: "0 -5px",
          }}
        >
          <colgroup>
            <col span="1" style={{ width: "clamp(150px, 15vw, 300px)" }} />
            <col span="1" style={{ width: "auto" }} />
            <col span="1" style={{ width: "85px" }} />
          </colgroup>
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
                      name === CONTENT_TYPE_NAME &&
                      WELL_KNOWN_TYPES[value] &&
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
