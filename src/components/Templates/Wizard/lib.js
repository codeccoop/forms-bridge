import { validateUrl } from "../../../lib/utils";

export function refToGroup(ref) {
  return ref.replace(/^#/, "").replace(/\/.*/, "");
}

export function getGroupFields(fields, group) {
  return fields.filter(({ ref }) => new RegExp("^\#" + group).test(ref));
}

export function validateBackend(backend, schema, fields) {
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

  if (schema.base_url && backend.base_url !== schema.base_url) {
    return false;
  } else if (!validateUrl(backend.base_url)) {
    return false;
  }

  return schema.headers.reduce((isValid, { name, value }) => {
    if (!isValid) return isValid;

    const header = backend.headers.find((header) => header.name === name);
    if (!header) return false;

    return header.value === value;
  }, isValid);
}

export function mockBackend(data, defaults = {}) {
  if (!data?.name || !data?.base_url) return;

  const mock = {
    name: data.name || defaults.name,
    base_url: data.base_url || defaults.base_url,
    headers: Object.keys(data)
      .filter((k) => !["name", "base_url"].includes(k))
      .map((k) => ({
        name: k,
        value: data[k],
      })),
  };

  if (Array.isArray(defaults.headers)) {
    defaults.headers.forEach(({ name, value }) => {
      if (!mock.headers.find((h) => h.name === name)) {
        mock.headers.push({ name, value });
      }
    });
  }

  return mock;
}
