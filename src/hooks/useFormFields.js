// vendor
import { useMemo } from "@wordpress/element";

// source
import { useForms } from "../providers/Forms";

export default function useFormFields({ formId }) {
  const forms = useForms();

  return useMemo(() => {
    const form = forms.find(({ id }) => id == formId);
    if (!form) return [];
    return form.fields.map(({ name, label }) => ({ name, label }));
  }, [forms]);
}
