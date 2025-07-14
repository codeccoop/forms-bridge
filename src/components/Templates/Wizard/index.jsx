// source
import { useTemplateConfig, useTemplate } from "../../../providers/Templates";
import useTab from "../../../hooks/useTab";
import useStepper from "./useStepper";
import { refToGroup, getGroupFields } from "./lib";
import useWiredBackend from "./useWiredBackend";
import useAuthorizedCredential from "./useAuthorizedCredential";
import { prependEmptyOption } from "../../../lib/utils";

const { Button, Notice } = wp.components;
const { useMemo, useState, useEffect, useCallback } = wp.element;
const apiFetch = wp.apiFetch;
const { __ } = wp.i18n;

export default function TemplateWizard({ integration, onSubmit }) {
  const [tab] = useTab();

  const [data, setData] = useState({});
  const [fetched, setFetched] = useState(false);
  const [fieldOptions, setFieldOptions] = useState([]);
  const [fetchError, setFetchError] = useState(false);

  const [config, setConfig] = useTemplateConfig();
  const [template] = useTemplate();

  const fields = useMemo(() => {
    const fields = config?.fields || [];
    return fields.filter(
      (f) => !Object.prototype.hasOwnProperty.call(f, "value")
    );
  }, [config]);

  const {
    done: isStepDone,
    move: moveStep,
    step,
    Step,
    name: group,
    reset: resetStepper,
    trailing: trailingStep,
  } = useStepper({ fields, integration, data });

  const groups = useMemo(() => {
    return fields.reduce((groups, field) => {
      const group = refToGroup(field.ref);

      return {
        ...groups,
        [group]: (groups[group] || []).concat([field]),
      };
    }, {});
  }, [fields]);

  const stepFields = useMemo(() => {
    if (!groups[group]) return [];
    if (!fetched) return groups[group];
    return groups[group].map((field) => {
      const options = fieldOptions.find(
        (fo) => fo.name === field.name && fo.ref === field.ref
      )?.options;

      if (options) {
        return {
          ...field,
          type: "options",
          options: prependEmptyOption(options),
        };
      }

      return field;
    });
  }, [groups, group, fieldOptions]);

  const defaults = useMemo(() => {
    const template = Object.fromEntries(
      Object.keys(groups).map((group) => [group, {}])
    );

    const fields = config?.fields || [];
    return fields.reduce((defaults, field) => {
      if (field.default || field.value) {
        const value = field.value || field.default;
        const group = refToGroup(field.ref);
        defaults[group] = defaults[group];
        defaults[group][field.name] = value;
      } else if (field.type === "options" && field.required) {
        const group = refToGroup(field.ref);
        defaults[group] = defaults[group] || {};

        if (Array.isArray(field.options)) {
          defaults[group][field.name] = field.options[0]?.value;
        }
      }

      return defaults;
    }, template);
  }, [config, groups]);

  useEffect(() => {
    setData(defaults);
    resetStepper();
  }, [fields, defaults]);

  const {
    credential,
    authorized,
    authorize,
    error: authError,
  } = useAuthorizedCredential({
    data: data.credential,
    fields: groups.credential,
  });

  const [backend, wired] = useWiredBackend({
    data: data.backend,
    fields: groups.backend,
    credential,
    authorized,
  });

  useEffect(() => {
    const fetched = !!wired;
    setFetched(fetched);

    if (!fetched) {
      setFieldOptions([]);
    }
  }, [wired]);

  useEffect(() => {
    if (group !== "backend" || !wired || !authorized) return;
    fetchOptions(backend, data.credential);
  }, [wired, data.credential]);

  const submit = useCallback(() => {
    setConfig({
      template,
      integration,
      fields: fields.map((field) => {
        const group = refToGroup(field.ref);

        if (
          Object.prototype.hasOwnProperty.call(data[group], field.name) &&
          data[group][field.name] !== null
        ) {
          if (
            field.type === "boolean" &&
            Array.isArray(data[group][field.name])
          ) {
            field.value = !!data[group][field.name][0];
          } else {
            field.value = data[group][field.name];
          }
        } else if (field.default) {
          field.value = field.default;
        } else if (!field.required) {
          switch (field.type) {
            // case "text":
            //   field.value = "";
            //   break;
            // case "number":
            //   field.value = 0;
            //   break;
            case "options":
              field.value = [];
              break;
            case "boolean":
              field.value = false;
              break;
            // default:
            //   field.value = "";
          }
        }

        return field;
      }),
    }).then((success) => onSubmit(success));
  }, [template, integration, fields, data]);

  const patchData = useCallback(
    (patch = null) => {
      const groupDefaults = defaults[group] || {};
      const current = data[group] || {};

      if (patch !== null) {
        patch = {
          ...groupDefaults,
          ...current,
          ...patch,
        };
      } else {
        patch = {};
      }

      setData({ ...data, [group]: patch });
    },
    [data, defaults, group]
  );

  const fetchOptions = useCallback(
    (backend, credential = {}) => {
      apiFetch({
        path: `forms-bridge/v1/${tab}/templates/${template}/options`,
        method: "POST",
        data: { backend, credential },
      })
        .then((fieldOptions) => {
          setFieldOptions(fieldOptions);
          setFetchError(false);
          setFetched(true);
        })
        .catch(() => {
          setFetched(false);
          setFetchError(true);
        });
    },
    [tab, template]
  );

  const isValid = useMemo(() => {
    return Object.keys(groups).reduce((isValid, group) => {
      const groupFields = getGroupFields(fields, group);

      return groupFields.reduce(
        (isValid, field) =>
          isValid && (!!data[group]?.[field.name] || !field.required),
        isValid
      );
    }, true);
  }, [data]);

  const needsAuth = credential && !authorized;
  const canGoForward =
    isStepDone && (group !== "backend" || fetched) && !needsAuth;

  if (!config?.fields.length) return;
  if (data[group] === undefined) return;

  return (
    <div style={{ width: "575px", minHeight: "125px" }}>
      <hr style={{ margin: "1rem 0" }} />
      {group === "backend" && fetchError && (
        <div style={{ marginBottom: "10px" }}>
          <Notice status="error" politeness="assertive" isDismissible={false}>
            {__("Unable to fetch data from the backend", "forms-bridge")}
          </Notice>
        </div>
      )}
      <Step
        integration={integration}
        fields={stepFields}
        data={data[group] || {}}
        setData={patchData}
        wired={wired}
        fetched={fetched}
      />
      {authError && (
        <div style={{ marginBottom: "10px" }}>
          <Notice status="error" politeness="assertive" isDismissible={false}>
            {__("The credential cannot be authorized", "forms-bridge")}
          </Notice>
        </div>
      )}
      {needsAuth && (
        <div style={{ margin: "10px 0" }}>
          <Notice
            isDismissible={false}
            status="warning"
            actions={[
              {
                label: __("Authorize", "forms-bridge"),
                onClick: authorize,
                variant: "secondary",
                size: "compact",
              },
            ]}
          >
            <p>
              {__(
                "Send an authorization request to validate the credential",
                "forms-bridge"
              )}
            </p>
          </Notice>
        </div>
      )}
      <div
        style={{
          padding: "1rem 0 0",
          display: "flex",
          justifyContent: "center",
          alignItems: "center",
          gap: "0.5rem",
        }}
      >
        <Button
          disabled={step <= 0}
          variant="secondary"
          onClick={() => moveStep(-1)}
        >
          {__("Previous", "forms-bridge")}
        </Button>
        {!trailingStep ? (
          <Button
            disabled={!canGoForward}
            variant="secondary"
            onClick={() => moveStep(1)}
          >
            {__("Next", "forms-bridge")}
          </Button>
        ) : (
          <Button disabled={!isValid} variant="primary" onClick={submit}>
            {__("Submit", "forms-bridge")}
          </Button>
        )}
      </div>
    </div>
  );
}
