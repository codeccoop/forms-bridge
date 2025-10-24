import { isset, defrost } from "./utils";

const cache = new Map();

export default function JsonFinger(data) {
  if (typeof data !== "object" || data === null) {
    throw new Error("Input data isn't a valid object type");
  }

  this.data = data;
}

JsonFinger.isConditional = function (pointer) {
  return ("" + pointer).indexOf("?") === 0;
};

JsonFinger.parse = function (pointer) {
  pointer = "" + pointer;

  if (JsonFinger.isConditional(pointer)) {
    pointer = pointer.slice(1);
  }

  if (cache.has(pointer)) {
    return cache.get(pointer).map((k) => k);
  }

  const len = pointer.length;
  const keys = [];
  let key = "";

  for (let i = 0; i < len; i++) {
    const char = pointer[i];

    if (char === ".") {
      if (key.length) {
        keys.push(key);
        key = "";
      }
    } else if (char === "[") {
      if (key.length) {
        keys.push(key);
        key = "";
      }

      i = i + 1;
      while (pointer[i] !== "]" && i < len) {
        key += pointer[i];
        i += 1;
      }

      if (key.length === 0) {
        key = Infinity;
      } else if (isNaN(key)) {
        if (!/^"[^"]+"$/.test(key)) {
          cache.set(pointer, []);
          return [];
        }

        key = JSON.parse(key);
      } else {
        key = +key;
      }

      keys.push(key);
      key = "";

      if (pointer.length - 1 > i) {
        if (pointer[i + 1] !== "." && pointer[i + 1] !== "[") {
          cache.set(pointer, []);
          return [];
        }
      }
    } else {
      key += char;
    }
  }

  if (key) {
    keys.push(key);
  }

  cache.set(pointer, keys);
  return keys.map((k) => k);
};

JsonFinger.sanitizeKey = function (key) {
  if (key === Infinity) {
    key = "[]";
  } else if (+key == key) {
    key = `[${key}]`;
  } else {
    key = key.trim();

    if (/( |\.|")/.test(key) && !/^\["[^"]+"\]$/.test(key)) {
      key = `["${key}"]`;
    }
  }

  return key;
};

JsonFinger.validate = function (pointer = "") {
  pointer = "" + pointer;

  if (!pointer.length) {
    return false;
  }

  return JsonFinger.parse(pointer).length > 0;
};

JsonFinger.pointer = function (keys, isConditional = false) {
  if (!Array.isArray(keys)) {
    return "";
  }

  const pointer = keys.reduce((pointer, key) => {
    if (key === Infinity) {
      key = "[]";
    } else if (+key == key) {
      key = `[${key}]`;
    } else {
      key = JsonFinger.sanitizeKey(key);

      if (key[0] !== "[" && pointer.length > 0) {
        key = "." + key;
      }
    }

    return pointer + key;
  }, "");

  if (isConditional) {
    return "?" + pointer;
  }

  return pointer;
};

JsonFinger.prototype.getData = function () {
  return this.data;
};

JsonFinger.prototype.get = function (pointer, expansion = []) {
  pointer = "" + pointer;

  if (!pointer) {
    return this.data;
  }

  if (isset(this.data, pointer)) {
    return this.data[pointer];
  }

  if (pointer.indexOf("[]") !== -1) {
    return this.getExpanded(pointer, expansion);
  }

  let value = null;
  try {
    const keys = JsonFinger.parse(pointer);

    value = this.data;
    for (let key of keys) {
      if (!isset(value, key)) {
        return;
      }

      value = value[key];
    }
  } catch {
    return null;
  }

  expansion.push(value);
  return value;
};

JsonFinger.prototype.getExpanded = function (pointer, expansion = []) {
  const flat = /\[\]$/.test(pointer);

  const parts = pointer.split("[]");
  const before = parts[0];
  const after = parts
    .slice(1)
    .filter((p, i) => p || i !== parts.length - 2)
    .join("[]");

  let values;
  if (!before) {
    if (!Array.isArray(this.data)) {
      return [];
    }

    values = this.data;
  } else {
    values = this.get(before);
  }

  if (!after.length || !Array.isArray(values)) {
    return values;
  }

  const isFrozen = Object.isFrozen(values);
  values = [...values];

  const len = isFrozen ? 1 : values.length;
  for (let i = 0; i < len; i++) {
    pointer = `${before}[${i}]${after}`;
    values[i] = this.get(pointer, expansion);
  }

  if (flat) {
    if (isFrozen) Object.freeze(expansion);
    return expansion;
  }

  if (isFrozen) Object.freeze(values);
  return values;
};

JsonFinger.prototype.set = function (pointer, value, unset = false) {
  if (isset(this.data, pointer)) {
    this.data[pointer] = value;
    return this.data;
  }

  if (pointer.indexOf("[]") !== -1) {
    return this.setExpanded(pointer, value, unset);
  }

  let data = this.data;
  const breadcrumb = [];

  try {
    const keys = JsonFinger.parse(pointer);
    if (keys.length === 1) {
      if (unset) {
        delete data[keys[0]];
      } else {
        data[keys[0]] = value;
      }

      this.data = data;
      return data;
    }

    let partial = data;

    for (let i = 0; i < keys.length - 1; i++) {
      if (!partial || typeof partial !== "object") {
        return data;
      }

      let key = keys[i];
      if (+key == key) {
        if (!Array.isArray(partial)) {
          return data;
        }

        key = +key;
      }

      if (!isset(partial, key)) {
        const nextKey = keys[i + 1] === undefined ? "no-key" : keys[i + 1];
        const isArray = +nextKey === nextKey;
        if (isArray) {
          partial[key] = [];
        } else {
          partial[key] = {};
        }
      }

      breadcrumb.push({ partial, key });
      partial = partial[key];
    }

    const { partial: parent, key: name } = breadcrumb[breadcrumb.length - 1];

    const key = keys.pop();

    const isFrozen = Object.isFrozen(partial);
    if (isFrozen) {
      partial = defrost(partial);
    }

    if (unset) {
      if (Array.isArray(partial)) {
        partial.splice(key, 1);
      } else if (partial && typeof partial === "object") {
        delete partial[key];
      }

      if (isFrozen) {
        parent[name] = Object.freeze(partial);
      }

      for (let i = breadcrumb.length - 1; i >= 0; i--) {
        const { partial: parent, key: name } = breadcrumb[i - 1] || {};

        let { partial, key } = breadcrumb[i];

        if (Object.keys(partial[key]).length) {
          break;
        }

        const isFrozen = Object.isFrozen(partial);
        if (isFrozen) partial = defrost(partial);

        if (Array.isArray(partial)) {
          partial.splice(key, 1);
        } else {
          delete partial[key];
        }

        if (isFrozen && parent) {
          parent[name] = Object.freeze(partial);
        }
      }
    } else {
      partial[key] = value;

      if (isFrozen) {
        parent[name] = Object.freeze(partial);
      }
    }
  } catch (err) {
    console.error(err);
    return this.data;
  }

  this.data = data;
  return data;
};

JsonFinger.prototype.setExpanded = function (pointer, values, unset) {
  const parts = pointer.split("[]");
  const before = parts[0];
  const after = parts
    .slice(1)
    .filter((p, i) => p || i !== parts.length - 2)
    .join("[]");

  if (!before) {
    if (!Array.isArray(values) || (!after && unset)) {
      return;
    }
  }

  if (unset) {
    values = this.get(before);
  }

  if (!Array.isArray(values)) {
    return;
  }

  const isFrozen = Object.isFrozen(values);
  for (let i = values.length - 1; i >= 0; i--) {
    pointer = `${before}[${i}]${after}`;

    if (unset) {
      this.unset(pointer);
    } else {
      this.set(pointer, values[i]);
    }
  }

  if (isFrozen && !unset) {
    values = this.get(before);
    this.set(before, Object.freeze(values));
  }

  return this.data;
};

JsonFinger.prototype.unset = function (pointer) {
  if (isset(this.data, pointer)) {
    if (+pointer == pointer) {
      if (Array.isArray(this.data)) {
        this.data.splice(pointer, 1);
      }
    } else {
      delete this.data[pointer];
    }

    return this.data;
  }

  return this.set(pointer, null, true);
};

JsonFinger.prototype.isset = function (pointer) {
  let key;
  const keys = JsonFinger.parse(pointer);

  switch (keys.length) {
    case 0:
      return false;
    case 1:
      key = keys[0];
      return isset(this.data, key);
    default:
      key = keys.pop();
      const pointer = JsonFinger.pointer(keys);
      const parent = this.get(pointer);

      if (pointer.indexOf("[]") === -1) {
        if (key === Infinity && Array.isArray(parent)) {
          return true;
        }

        return isset(parent, key);
      }

      if (!Array.isArray(parent)) {
        return false;
      }

      if (key === Infinity) {
        return true;
      }

      for (let item of parent) {
        if (isset(item, key)) {
          return true;
        }
      }

      return false;
  }
};
