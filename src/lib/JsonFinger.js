const cache = new Map();

function isset(obj, attr) {
  if (!obj || typeof obj !== "object") {
    return false;
  }

  if (Array.isArray(obj)) {
    return obj.length > attr;
  }

  return Object.prototype.hasOwnProperty.call(obj, attr);
}

function JsonFinger(data) {
  if (typeof data !== "object" || data === null) {
    throw new Error("Input data isn't a valid object type");
  }

  this.data = data;
}

JsonFinger.isConditional = function (pointer) {
  return pointer.indexOf("?") === 0;
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
  const expanded = /\[\]$/.test(pointer);

  const parts = pointer.split("[]").filter((p) => p);
  const before = parts[0];
  let after = parts.slice(1).join("[]");

  const value = this.get(before);

  if (!after.length || !Array.isArray(value)) {
    return value;
  }

  const isFrozen = Object.isFrozen(value);
  const items = value.map((d) => d);

  const len = isFrozen ? 1 : items.length;
  for (let i = 0; i < len; i++) {
    pointer = `${before}[${i}]${after}`;
    items[i] = this.get(pointer, expansion);
  }

  if (expanded) {
    if (isFrozen) Object.freeze(expansion);
    return expansion;
  }

  if (isFrozen) Object.freeze(items);
  return items;
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
    let partial = data;

    let i;
    for (i = 0; i < keys.length - 1; i++) {
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

    let key = keys[i];
    if (unset) {
      if (Array.isArray(partial)) {
        partial.splice(key, 1);
      } else if (partial && typeof partial === "object") {
        delete partial[key];
      }

      for (let i = breadcrumb.length - 1; i >= 0; i--) {
        const { partial, key } = breadcrumb[i];

        if (Object.keys(partial[key]).length) {
          break;
        }

        if (Array.isArray(partial)) {
          partial.splice(key, 1);
        } else {
          delete partial[key];
        }
      }
    } else {
      partial[key] = value;
    }
  } catch {
    return data;
  }

  this.data = data;
  return data;
};

JsonFinger.prototype.setExpanded = function (pointer, values, unset) {
  const parts = pointer.split("[]").filter((p) => p);
  const before = parts[0];
  const after = parts.slice(1).join("[]");

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

  if (isFrozen) {
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
        return isset(parent, key);
      }

      if (!Array.isArray(parent)) {
        return false;
      }

      for (let item of parent) {
        if (isset(item, key)) {
          return true;
        }
      }

      return false;
  }
};

export default JsonFinger;
