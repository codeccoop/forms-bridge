// source
import { useError } from "../../providers/Error";
import { useTemplate, useTemplateConfig } from "../../providers/Templates";
import { useTemplates } from "../../hooks/useAddon";
import { useIntegrations } from "../../hooks/useGeneral";
import Wizard from "./Wizard";
import { prependEmptyOption } from "../../lib/utils";

const {
  Modal,
  Button,
  SelectControl,
  __experimentalSpacer: Spacer,
} = wp.components;
const { useState, useEffect, useMemo, useCallback } = wp.element;
const { __ } = wp.i18n;

export default function Templates() {
  const [templates] = useTemplates();
  const [, setTemplate] = useTemplate();
  const [templateConfig] = useTemplateConfig();

  const [templateData, setTemplateData] = useState({});
  const [wired, setWired] = useState(null);
  const [done, setDone] = useState(false);
  const [error, setError] = useError();

  const [integrations] = useIntegrations();
  const integrationOptions = useMemo(() => {
    return integrations
      .filter(({ enabled }) => enabled)
      .map(({ name, title }) => ({
        value: name,
        label: title,
      }))
      .sort((a, b) => {
        return a.label > b.label ? 1 : -1;
      });
  }, [integrations]);

  const [integration, setIntegration] = useState(
    integrationOptions[0]?.value || ""
  );

  useEffect(() => {
    if (!integration && integrationOptions.length) {
      setIntegration(integrationOptions[0].value);
    }
  }, [integration, integrationOptions]);

  const templateOptions = useMemo(() => {
    return prependEmptyOption(
      templates
        .filter(({ integrations }) => integrations.includes(integration))
        .map(({ name, title }) => ({
          label: title,
          value: name,
        }))
    ).sort((a, b) => {
      return a.label > b.label ? 1 : -1;
    });
  }, [templates, integration]);

  const [isOpen, setIsOpen] = useState(false);

  useEffect(() => {
    if (done) setTemplate(null);
  }, [done]);

  useEffect(() => {
    if (error) setIsOpen(false);
  }, [error]);

  useEffect(() => {
    if (!isOpen) {
      setTemplate(null);
      setTemplateData({});
      setDone(false);
    }
  }, [isOpen]);

  const onSubmit = useCallback((success) => {
    if (success) setDone(true);
    else if (success === false) {
      setError(__("Unsuccessful template submit", "forms-bridge"));
    }
  });

  if (!templates.length) return null;

  return (
    <>
      <Button
        disabled={!!error || !integrations.length}
        variant="primary"
        onClick={() => setIsOpen(true)}
        style={{
          width: "150px",
          marginTop: "auto",
          justifyContent: "center",
        }}
        __next40pxDefaultSize
      >
        {__("Use a template", "forms-bridge")}
      </Button>
      {isOpen && (
        <Modal
          title={__("Templates", "forms-bridge")}
          onRequestClose={() => {
            setTemplate(null);
            setIsOpen(false);
          }}
        >
          {(done && (
            <>
              <p style={{ fontSize: "1rem" }}>
                {__(
                  "Congratulations, you've created a new form bridge!",
                  "forms-bridge"
                )}
              </p>
              <Button
                variant="primary"
                onClick={() => setIsOpen(false)}
                style={{
                  width: "150px",
                  margin: "1.5rem auto 0",
                  display: "block",
                }}
                __next40pxDefaultSize
              >
                {__("Close", "forms-bridge")}
              </Button>
            </>
          )) || (
            <>
              {integrationOptions.length > 1 && (
                <>
                  <SelectControl
                    label={__("Target integration", "forms-bridge")}
                    options={integrationOptions}
                    value={integration}
                    onChange={setIntegration}
                    __nextHasNoMarginBottom
                    __next40pxDefaultSize
                  />
                  <Spacer paddingY="calc(6px)" />
                </>
              )}
              <SelectControl
                label={__("Select a template", "forms-bridge")}
                options={templateOptions}
                onChange={setTemplate}
                __nextHasNoMarginBottom
                __next40pxDefaultSize
              />
            </>
          )}
          {(templateConfig?.description && (
            <p
              style={{ maxWidth: "575px" }}
              dangerouslySetInnerHTML={{ __html: templateConfig.description }}
            ></p>
          )) ||
            null}
          <Wizard
            integration={integration}
            onSubmit={onSubmit}
            data={templateData}
            setData={setTemplateData}
            wired={wired}
            setWired={setWired}
          />
        </Modal>
      )}
    </>
  );
}
