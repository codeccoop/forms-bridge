import ContentType from "./ContentType";
import { useCredentials } from "../../hooks/useHttp";
import FieldWrapper from "../FieldWrapper";
import { prependEmptyOption } from "../../lib/utils";

const { useMemo } = wp.element;
const { TextControl, SelectControl } = wp.components;
const { __ } = wp.i18n;

export default function BackendFields({ state, setState, errors }) {
  const [credentials] = useCredentials();

  const credentialOptions = useMemo(() => {
    return prependEmptyOption(
      credentials
        .map(({ name }) => ({ value: name, label: name }))
        .sort((a, b) => (a.name > b.name ? 1 : -1))
    );
  }, [credentials]);

  return (
    <>
      <FieldWrapper>
        <TextControl
          label={__("Name", "forms-bridge")}
          help={
            errors.name && __("This name is already in use", "forms-bridge")
          }
          value={state.name}
          onChange={(name) => setState({ ...state, name })}
          __nextHasNoMarginBottom
          __next40pxDefaultSize
        />
      </FieldWrapper>
      <FieldWrapper>
        <TextControl
          label={__("Base URL", "forms-bridge")}
          help={errors.base_url && __("Invalid base URL", "forms-bridge")}
          value={state.base_url}
          onChange={(base_url) => setState({ ...state, base_url })}
          __nextHasNoMarginBottom
          __next40pxDefaultSize
        />
      </FieldWrapper>
      <ContentType
        headers={state.headers}
        setHeaders={(headers) => setState({ ...state, headers })}
      />
      <FieldWrapper>
        <SelectControl
          label={__("Authentication", "forms-bridge")}
          value={state.credential || ""}
          onChange={(credential) => setState({ ...state, credential })}
          options={credentialOptions}
          __next40pxDefaultSize
          __nextHasNoMarginBottom
        />
      </FieldWrapper>
    </>
  );
}
