import { useTemplateConfig } from "../../../../providers/Templates";
import TemplateStep from "./Step";
import Field from "../../Field";
import { prependEmptyOption, sortByNamesOrder } from "../../../../lib/utils";
import { useCredentials } from "../../../../hooks/useAddon";
import { validateCredential } from "../lib";
import diff from "../../../../lib/diff";

const { useMemo, useState, useEffect, useRef } = wp.element;
const { SelectControl } = wp.components;
const { __ } = wp.i18n;

const FIELDS_ORDER = ["name"];

export default function CredentialStep({ fields, data, setData }) {
  const [credentials] = useCredentials();
  const { credential: config } = useTemplateConfig();

  const names = useMemo(
    () => new Set(credentials.map(({ name }) => name)),
    [credentials]
  );

  const sortedFields = useMemo(
    () => sortByNamesOrder(fields, FIELDS_ORDER),
    [fields]
  );

  const [state, setState] = useState({ ...data });

  const defaults = useMemo(() => {
    return fields.reduce((defaults, field) => {
      let val = field.default || config?.[field.name] || "";
      if (!val && field.type === "options" && field.required) {
        val = field.options[0].value;
      }

      defaults[field.name] = val;
      return defaults;
    }, {});
  }, [fields, config]);

  const validCredentials = useMemo(() => {
    return credentials.filter((credential) =>
      validateCredential(credential, fields)
    );
  }, [credentials, fields]);

  const credentialOptions = useMemo(() => {
    return prependEmptyOption(
      validCredentials
        .map(({ name }) => ({ label: name, value: name }))
        .sort((a, b) => (a.label > b.label ? 1 : -1))
    );
  }, [validCredentials]);

  const [reuse, setReuse] = useState(() => {
    return (
      credentialOptions.find(({ value }) => value === data.name)?.value || ""
    );
  });

  const nameConflict = useMemo(
    () => state.name && names.has(state.name.trim()),
    [names, state.name]
  );

  const credential = useMemo(() => {
    let credential = validCredentials.find((c) => c.name === reuse);
    if (credential) return credential;
    if (validateCredential(state, fields)) {
      return state;
    }
  }, [validCredentials, reuse, state, nameConflict]);

  useEffect(() => {
    if (!credential) {
      setData(null);
      return;
    }

    if (reuse) {
      setState({ ...defaults });
    }

    setData({ ...credential });
  }, [credential]);

  const fromConfig = useRef(config);
  useEffect(() => {
    if (diff(config, fromConfig.current)) {
      setReuse("");
    }

    return () => {
      fromConfig.current = config;
    };
  }, [config]);

  useEffect(() => {
    const credential = validCredentials.find((c) => c.name === data.name);
    if (credential) {
      setReuse(credential.name);
    }
  }, [data.name, validCredentials]);

  return (
    <TemplateStep
      name={__("Credential", "forms-bridge")}
      description={__("Configure the backend credentials", "forms-bridge")}
    >
      {credentialOptions.length > 0 && (
        <SelectControl
          label={__("Reuse an existing credential", "forms-bridge")}
          value={reuse}
          options={credentialOptions}
          onChange={setReuse}
          __nextHasNoMarginBottom
          __next40pxDefaultSize
        />
      )}
      {!reuse &&
        sortedFields.map((field) => (
          <Field
            data={{
              ...field,
              value: data[field.name] || "",
              onChange: (value) => setData({ [field.name]: value }),
            }}
            error={
              field.name === "name" &&
              nameConflict &&
              __("This name is already in use", "forms-bridge")
            }
          />
        ))}
    </TemplateStep>
  );
}
