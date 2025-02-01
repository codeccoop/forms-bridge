// vendor
import React from "react";
import {
  TextControl,
  SelectControl,
  Button,
  CheckboxControl,
  __experimentalSpacer as Spacer,
} from "@wordpress/components";
import { useEffect } from "@wordpress/element";

const { __ } = wp.i18n;

const WELL_KNOWN_CONTENT_TYPES = {
  "application/json": "JSON",
  "application/x-www-form-urlencoded": "URL Encoded",
  "multipart/form-data": "Binary files",
};

function ContentTypeHeader({ setValue, value }) {
  return (
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
        {__("Content encoding", "forms-bridge")}
        <br />
        <span
          style={{
            color: "#757575",
            fontStyle: "normal",
            fontSize: "12px",
            marginTop: "calc(8px)",
            textTransform: "none",
            fontWeight: "400",
          }}
        >
          {__(
            "Select how Forms Bridge should encode your form submissions.",
            "forms-bridge"
          )}
        </span>
        <br />
        <span
          style={{
            color: "#757575",
            fontStyle: "normal",
            fontSize: "12px",
            marginTop: "calc(8px)",
            textTransform: "none",
            fontWeight: "400",
          }}
        >
          ⚠{" "}
          {__(
            "If your backend uses custom encoding, Forms Bridge will need a string payload. You can use the `forms_bridge_payload` hook to encode your form submission as string."
          )}
        </span>
      </label>
      <div style={{ width: "250px", marginTop: "calc(8px)" }}>
        <SelectControl
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
    </div>
  );
}

export default function BackendHeaders({ headers, setHeaders }) {
  const __ = wp.i18n.__;

  const contentType =
    headers.find((header) => header.name === "Content-Type")?.value || "";

  const setContentType = (type) => {
    const index = headers.findIndex((header) => header.name === "Content-Type");
    if (index === -1) {
      addHeader("Content-Type", type);
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

  const addHeader = (name = "Accept", value = "application/json") => {
    const newHeaders = headers.concat([{ name, value }]);
    setHeaders(newHeaders);
  };

  const dropHeader = (index) => {
    const newHeaders = headers.slice(0, index).concat(headers.slice(index + 1));
    setHeaders(newHeaders);
  };

  useEffect(() => {
    if (!(headers.length && headers.find((h) => h.name === "Content-Type")))
      addHeader("Content-Type", "application/json");
  }, [headers]);

  return (
    <>
      <ContentTypeHeader value={contentType} setValue={setContentType} />
      <Spacer paddingY="calc(8px)" />
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
          {__("Backend HTTP Headers", "forms-bridge")}
        </label>
        <table
          style={{
            width: "calc(100% + 10px)",
            borderSpacing: "5px",
            margin: "0 -5px",
          }}
        >
          <tbody>
            {headers.map(({ name, value }, i) => (
              <tr key={i}>
                <td>
                  <TextControl
                    placeholder={__("Header-Name", "forms-bridge")}
                    value={name}
                    onChange={(value) => setHeader("name", i, value)}
                    __nextHasNoMarginBottom
                    __next40pxDefaultSize
                  />
                </td>
                <td>
                  <TextControl
                    placeholder={__("Value", "forms-bridge")}
                    value={value}
                    onChange={(value) => setHeader("value", i, value)}
                    __nextHasNoMarginBottom
                    __next40pxDefaultSize
                  />
                </td>
                <td style={{ borderLeft: "1rem solid transparent" }}>
                  <Button
                    disabled={name === "Content-Type"}
                    isDestructive
                    variant="secondary"
                    onClick={() => dropHeader(i)}
                    __next40pxDefaultSize
                  >
                    {__("Drop", "forms-bridge")}
                  </Button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        <Spacer paddingY="calc(3px)" />
        <Button
          variant="secondary"
          onClick={() => addHeader()}
          __next40pxDefaultSize
        >
          {__("Add header", "forms-bridge")}
        </Button>
      </div>
    </>
  );
}
