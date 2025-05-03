import JsonFinger from "../../lib/JsonFinger";
import { useApiFields } from "../../providers/ApiSchema";
import DropdownSelect from "../DropdownSelect";

const { BaseControl, TextControl, Button } = wp.components;
const { useEffect, useState, useRef, useMemo } = wp.element;
const { __ } = wp.i18n;

const tagOptions = [
  {
    label: __("Submission ID", "forms-bridge"),
    value: "$submission_id",
  },
  {
    label: __("Form ID", "forms-bridge"),
    value: "$form_id",
  },
  {
    label: __("Form title", "forms-bridge"),
    value: "$form_title",
  },
  {
    label: __("Site title", "forms-bridge"),
    value: "$site_site",
  },
  {
    label: __("Site description", "forms-bridge"),
    value: "$site_description",
  },
  {
    label: __("Site URL", "forms-bridge"),
    value: "$site_url",
  },
  {
    label: __("Blog URL", "forms-bridge"),
    value: "$blog_url",
  },
  {
    label: __("Admin email", "forms-bridge"),
    value: "$admin_email",
  },
  {
    label: __("WP Version", "forms-bridge"),
    value: "$wp_version",
  },
  {
    label: __("IP address", "forms-bridge"),
    value: "$ip_address",
  },
  {
    label: __("Referer", "forms-bridge"),
    value: "$referer",
  },
  {
    label: __("User agent", "forms-bridge"),
    value: "$user_agent",
  },
  {
    label: __("Browser locale", "forms-bridge"),
    value: "$browser_locale",
  },
  {
    label: __("Page locale", "forms-bridge"),
    value: "$locale",
  },
  {
    label: __("Page language", "forms-bridge"),
    value: "$language",
  },
  {
    label: __("Datetime", "forms-bridge"),
    value: "$datetime",
  },
  {
    label: __("ISO Date", "forms-bridge"),
    value: "$iso_date",
  },
  {
    label: __("Timestamp", "forms-bridge"),
    value: "$timestamp",
  },
  {
    label: __("User ID", "forms-bridge"),
    value: "$user_id",
  },
  {
    label: __("user_login", "forms-bridge"),
    value: "$user_login",
  },
  {
    label: __("User name", "forms-bridge"),
    value: "$user_name",
  },
  {
    label: __("User email", "forms-bridge"),
    value: "$user_email",
  },
];

const CSS = `.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
  overflow-y: auto;
  overflow-x: hidden;
}

.scrollbar-hide::-webkit-scrollbar {
  display: none;
}

.scrollbar-hide table tr td {
  padding: 1em 0.25em;
}

.scrollbar-hide table tr td:first-child {
  padding: 1em 0.5em 1em 5px;
}

.scrollbar-hide table tr td:last-child {
  padding: 1em 10px 1em 0.25em;
  white-space: nowrap;
}

.scrollbar-hide table tr:not(:last-child) td {
  border-bottom: 1px solid #ccc;
}

.components-popover__content li:hover,
.components-popover__content li:focus {
  color: var(--wp-components-color-accent, var(--wp-admin-theme-color, #3858e9));
}

.components-popover__content li:focus {
  margin: 1px;
  outline: 1px solid;
  border-radius: 0px;
}`;

const INVALID_TO_STYLE = {
  "--wp-components-color-accent": "#cc1818",
  "color":
    "var(--wp-components-color-accent, var(--wp-admin-theme-color, #3858e9))",
  "borderColor":
    "var(--wp-components-color-accent, var(--wp-admin-theme-color, #3858e9))",
};

function useInputStyle(name = "") {
  const inputStyle = {
    height: "40px",
    paddingLeft: "12px",
    paddingRight: "12px",
    fontSize: "13px",
    borderRadius: "2px",
    width: "100%",
    display: "block",
  };

  if (name.length && !JsonFinger.validate(name, "set")) {
    return { ...inputStyle, ...INVALID_TO_STYLE };
  }

  return inputStyle;
}

export default function CustomFieldsTable({ customFields, setCustomFields }) {
  const apiFields = useApiFields();

  const fieldOptions = useMemo(() => {
    return apiFields.map((field) => ({
      value: field.name,
      label: `${field.name} | ${field.schema.type}`,
    }));
  }, [apiFields]);

  const tableWrapper = useRef();
  const [fieldSelector, setFieldSelector] = useState(-1);
  const [tagSelector, setTagSelector] = useState(-1);

  const setCustomField = (attr, index, value) => {
    const newCustomFields = customFields.map((customField, i) => {
      if (index === i) {
        customField[attr] = value;
      }

      return { ...customField };
    });

    setCustomFields(newCustomFields);
  };

  const addCustomField = (index) => {
    const newCustomFields = customFields
      .slice(0, index)
      .concat([{ name: "", value: "" }])
      .concat(customFields.slice(index, customFields.length));

    if (index === customFields.length) {
      setTimeout(() => {
        tableWrapper.current.scrollTo(
          0,
          tableWrapper.current.children[0].offsetHeight
        );
      }, 100);
    }

    setCustomFields(newCustomFields);
  };

  const dropCustomField = (index) => {
    const newCustomFields = customFields
      .slice(0, index)
      .concat(customFields.slice(index + 1));

    setCustomFields(newCustomFields);
  };

  useEffect(() => {
    if (!customFields.length) addCustomField(0);
  }, [customFields]);

  const style = useRef(document.createElement("style"));
  useEffect(() => {
    style.current.appendChild(document.createTextNode(CSS));
    document.head.appendChild(style.current);

    return () => {
      document.head.removeChild(style.current);
    };
  }, []);

  return (
    <>
      <div ref={tableWrapper} className="scrollbar-hide" style={{ flex: 1 }}>
        <table
          style={{
            width: "calc(100% + 10px)",
            margin: "0 -5px",
            borderSpacing: "0px",
          }}
        >
          <thead>
            <tr>
              <th aria-hidden="true"></th>
              <th
                scope="col"
                style={{ textAlign: "left", padding: "1em 0 0 0.5em" }}
              >
                {__("Name", "forms-bridge")}
              </th>
              <th
                scope="col"
                style={{ textAlign: "left", padding: "1em 0 0 0.5em" }}
              >
                {__("Value", "forms-bridge")}
              </th>
              <th aria-hidden="true"></th>
            </tr>
          </thead>
          <tbody>
            {customFields.map(({ name, value, index }, i) => (
              <tr key={index}>
                <td style={{ width: 0 }}>{i + 1}.</td>
                <td>
                  <div style={{ display: "flex" }}>
                    <div style={{ flex: 1 }}>
                      <BaseControl __nextHasNoMarginBottom>
                        <input
                          type="text"
                          value={name}
                          onChange={(ev) =>
                            setCustomField("name", i, ev.target.value)
                          }
                          style={useInputStyle(name)}
                        />
                      </BaseControl>
                    </div>
                    {fieldOptions.length > 0 && (
                      <Button
                        style={{
                          height: "40px",
                          width: "40px",
                          justifyContent: "center",
                          marginLeft: "2px",
                        }}
                        size="compact"
                        variant="secondary"
                        onClick={() => setFieldSelector(i)}
                        __next40pxDefaultSize
                      >
                        $
                        {fieldSelector === i && (
                          <DropdownSelect
                            title={__("Fields", "forms-bridge")}
                            tags={fieldOptions}
                            onChange={(fieldName) => {
                              setFieldSelector(-1);
                              setCustomField("name", i, fieldName);
                            }}
                            onFocusOutside={() => setFieldSelector(-1)}
                          />
                        )}
                      </Button>
                    )}
                  </div>
                </td>
                <td>
                  <div style={{ display: "flex" }}>
                    <div style={{ flex: 1 }}>
                      <TextControl
                        value={value}
                        onChange={(value) => setCustomField("value", i, value)}
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                      />
                    </div>
                    <Button
                      disabled={!name}
                      style={{
                        height: "40px",
                        width: "40px",
                        justifyContent: "center",
                        marginLeft: "2px",
                      }}
                      size="compact"
                      variant="secondary"
                      onClick={() => setTagSelector(i)}
                      __next40pxDefaultSize
                    >
                      $
                      {tagSelector === i && (
                        <DropdownSelect
                          title={__("Tags", "forms-bridge")}
                          tags={tagOptions}
                          onChange={(tag) => {
                            setTagSelector(-1);
                            setCustomField("value", i, value + tag);
                          }}
                          onFocusOutside={() => setTagSelector(-1)}
                        />
                      )}
                    </Button>
                  </div>
                </td>
                <td style={{ width: 0 }}>
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
                      onClick={() => addCustomField(i + 1)}
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
                      size="compact"
                      isDestructive
                      variant="secondary"
                      onClick={() => dropCustomField(i)}
                      style={{
                        width: "40px",
                        height: "40px",
                        justifyContent: "center",
                      }}
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
