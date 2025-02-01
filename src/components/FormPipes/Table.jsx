// vendor
import React from "react";
import {
  SelectControl,
  TextControl,
  Button,
  __experimentalSpacer as Spacer,
} from "@wordpress/components";
import { useEffect, useMemo } from "@wordpress/element";

const castOptions = [
  {
    value: "string",
    label: "String",
  },
  {
    value: "integer",
    label: "Integer",
  },
  {
    value: "float",
    label: "Decimal",
  },
  {
    value: "boolean",
    label: "Boolean",
  },
  {
    value: "json",
    label: "JSON",
  },
  {
    value: "csv",
    label: "CSV",
  },
  {
    value: "concat",
    label: "Concatenate",
  },
  {
    value: "null",
    label: "Ignore",
  },
];

export default function PipesTable({ form, pipes, setPipes, done }) {
  const __ = wp.i18n.__;

  const fields = useMemo(() => {
    if (!form) return [];
    return form.fields
      .filter(({ is_file }) => !is_file)
      .map(({ name, label }) => ({ name, label }));
  }, [form]);

  const fromOptions = [
    { label: "", value: "" },
    { label: __("Submission ID", "forms-bridge"), value: "submission_id" },
  ].concat(
    fields.map((field) => ({
      label: __(field.label, "forms-bridge"),
      value: field.name,
    }))
  );

  const setPipe = (attr, index, value) => {
    const newPipes = pipes.map((pipe, i) => {
      if (index === i) {
        pipe[attr] = value;
        if (attr === "from" && pipe.to !== value) {
          pipe.to = value;
        }
      }
      return { ...pipe };
    });

    setPipes(newPipes);
  };

  const addPipe = () => {
    const newPipes = pipes.concat([{ from: "", to: "", cast: "string" }]);
    setPipes(newPipes);
  };

  const dropPipe = (index) => {
    const newPipes = pipes.slice(0, index).concat(pipes.slice(index + 1));
    setPipes(newPipes);
  };

  useEffect(() => {
    if (!pipes.length) addPipe();
  }, [pipes]);

  return (
    <div className="components-base-control__label">
      <div style={{ display: "flex" }}>
        <label
          className="components-base-control__label"
          style={{
            fontSize: "11px",
            textTransform: "uppercase",
            fontWeight: 500,
            lineHeight: "32px",
          }}
        >
          {__("Form format pipes", "forms-bridge")}
        </label>
      </div>
      <table
        style={{
          width: "calc(100% + 10px)",
          borderSpacing: "5px",
          margin: "0 -5px",
        }}
      >
        <tbody>
          {pipes.map(({ from, to, cast }, i) => (
            <tr key={i}>
              <td>
                <SelectControl
                  placeholder={__("From", "forms-bridge")}
                  value={from}
                  onChange={(value) => setPipe("from", i, value)}
                  options={fromOptions}
                  __nextHasNoMarginBottom
                  __next40pxDefaultSize
                />
              </td>
              <td>
                <TextControl
                  placeholder={__("To", "forms-bridge")}
                  value={to}
                  onChange={(value) => setPipe("to", i, value)}
                  __nextHasNoMarginBottom
                  __next40pxDefaultSize
                />
              </td>
              <td>
                <SelectControl
                  placeholder={__("Cast as", "forms-bridge")}
                  value={cast || "string"}
                  onChange={(value) => setPipe("cast", i, value)}
                  options={castOptions.map(({ label, value }) => ({
                    label: __(label, "forms-bridge"),
                    value,
                  }))}
                  __nextHasNoMarginBottom
                  __next40pxDefaultSize
                />
              </td>
              <td>
                <Button
                  isDestructive
                  variant="secondary"
                  onClick={() => dropPipe(i)}
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
      <div style={{ display: "flex", gap: "0.5rem" }}>
        <Button
          variant="secondary"
          onClick={() => addPipe()}
          __next40pxDefaultSize
        >
          {__("Add", "forms-bridge")}
        </Button>
        <Button variant="primary" onClick={() => done()} __next40pxDefaultSize>
          {__("Done", "posts-bridge")}
        </Button>
      </div>
    </div>
  );
}
