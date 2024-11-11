// vendor
import React from "react";
import {
  TextControl,
  Button,
  __experimentalSpacer as Spacer,
} from "@wordpress/components";
import { useEffect } from "@wordpress/element";

// source
import { useI18n } from "../../providers/I18n";

export default function BackendHeaders({ headers, setHeaders }) {
  const __ = useI18n();
  const setHeader = (attr, index, value) => {
    const newHeaders = headers.map((header, i) => {
      if (index === i) header[attr] = value;
      return { ...header };
    });

    setHeaders(newHeaders);
  };

  const addHeader = () => {
    const newHeaders = headers.concat([{ name: "", value: "" }]);
    setHeaders(newHeaders);
  };

  const dropHeader = (index) => {
    const newHeaders = headers.slice(0, index).concat(headers.slice(index + 2));
    setHeaders(newHeaders);
  };

  useEffect(() => {
    if (!headers.length) addHeader();
  }, [headers]);

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
        {__("Backend HTTP Headers", "wpct-erp-forms")}
      </label>
      <table style={{ width: "100%" }}>
        <tbody>
          {headers.map(({ name, value }, i) => (
            <tr key={i}>
              <td>
                <TextControl
                  placeholder={__("Header-Name", "wpct-erp-forms")}
                  value={name}
                  onChange={(value) => setHeader("name", i, value)}
                  __nextHasNoMarginBottom
                />
              </td>
              <td>
                <TextControl
                  placeholder={__("Value", "wpct-erp-forms")}
                  value={value}
                  onChange={(value) => setHeader("value", i, value)}
                  __nextHasNoMarginBottom
                />
              </td>
              <td style={{ borderLeft: "1rem solid transparent" }}>
                <Button
                  isDestructive
                  variant="secondary"
                  onClick={() => dropHeader(i)}
                  style={{ height: "32px" }}
                >
                  {__("Drop", "wpct-erp-forms")}
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
        style={{ height: "32px" }}
      >
        {__("Add", "wpct-erp-forms")}
      </Button>
    </div>
  );
}
