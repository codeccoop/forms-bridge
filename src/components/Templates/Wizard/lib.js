import { validateUrl } from "../../../lib/utils";

export function refToGroup(ref) {
  return ref.replace(/^#/, "").replace(/\/.*/, "");
}

export function getGroupFields(fields, group) {
  return fields.filter(({ ref }) => new RegExp("^\#" + group).test(ref));
}

export function validateCredential(credential, template, fields) {
  if (!credential?.name) return false;

  if (template?.schema && template.schema !== credential.schema) {
    return false;
  }

  return fields.reduce((isValid, { name, required }) => {
    if (!isValid || !required) return isValid;

    const val = credential[name];
    return isValid && val !== undefined && val !== null && val !== "";
  }, true);
}

export function validateBackend(backend, template, fields) {
  if (!backend?.name) return false;

  const isValid = fields.reduce((isValid, { name, ref, required }) => {
    if (!isValid || !required) return isValid;

    let value;
    if (ref === "#backend/headers[]") {
      value = backend.headers.find((header) => header.name === name)?.value;
    } else {
      value = backend[name];
    }

    return isValid && value !== undefined && value !== null && value !== "";
  }, true);

  if (!isValid) return isValid;

  if (template.base_url && backend.base_url !== template.base_url) {
    if (template.base_url !== backend.base_url) {
      const url = template.base_url.replace(/{\w+}/g, ".+");
      if (new RegExp(url).test(backend.base_url) === false) {
        return false;
      }
    }
  } else if (!validateUrl(backend.base_url)) {
    return false;
  }

  return template.headers.reduce((isValid, { name, value }) => {
    if (!isValid) return isValid;

    const header = backend.headers.find((header) => header.name === name);
    if (!header) return false;

    return header.value === value;
  }, isValid);
}

export function mockBackend(data, template = {}, fields) {
  if (!data?.name || !data?.base_url) return;

  const mock = {
    name: data.name || template.name,
    base_url: data.base_url || template.base_url,
    credential: data.credential || "",
    headers: Object.keys(data)
      .filter((k) => !["name", "base_url", "credential"].includes(k))
      .map((k) => ({
        name: k,
        value: data[k],
      })),
  };

  if (Array.isArray(template.headers)) {
    template.headers.forEach(({ name, value }) => {
      if (!mock.headers.find((h) => h.name === name)) {
        mock.headers.push({ name, value });
      }
    });
  }

  fields.forEach((field) => {
    if (!field.value) return;

    if (field.ref === "#backend/headers[]") {
      const header = mock.headers.find((h) => h.name === field.name);
      if (header) header.value = field.value;
      else mock.headers.push({ name: field.name, value: field.value });
    } else {
      mock[field.name] = field.value;
    }
  });

  return mock;
}
