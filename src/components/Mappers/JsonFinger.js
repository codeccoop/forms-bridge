const cache = new Map();

function isset(obj, attr) {
  if (!obj || typeof obj !== "object") {
    return false;
  }

  if (Array.isArray(obj)) {
    return obj[+attr] !== undefined;
  }

  return Object.prototype.hasOwnProperty.call(obj, attr);
}

function JsonFinger(data) {
  if (typeof data !== "object" || data === null) {
    throw new Error("Input data isn't a valid object type");
  }

  this.data = JSON.parse(JSON.stringify(data));
}

JsonFinger.parse = function (pointer) {
  pointer = "" + pointer;

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
        cache.set(pointer, []);
        return [];
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
  if (+key === key) {
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

JsonFinger.pointer = function (keys) {
  if (!Array.isArray(keys)) {
    return "";
  }

  return keys.reduce((pointer, key) => {
    const isArray = +key === key;
    if (isArray) {
      key = `[${key}]`;
    } else {
      key = JsonFinger.sanitizeKey(key);

      if (key[0] !== "[" && pointer.length > 0) {
        key = "." + key;
      }
    }

    return pointer + key;
  }, "");
};

JsonFinger.prototype.getData = function () {
  return JSON.parse(JSON.stringify(this.data));
};

JsonFinger.prototype.get = function (pointer) {
  pointer = "" + pointer;

  if (isset(this.data, pointer)) {
    return this.getData()[pointer];
  }

  let value = null;
  try {
    const keys = JsonFinger.parse(pointer);

    value = this.getData();
    for (const key of keys) {
      if (!isset(value, key)) {
        return;
      }

      value = value[key];
    }
  } catch {
    return;
  }

  return value;
};

JsonFinger.prototype.set = function (pointer, value, unset = false) {
  if (isset(this.data, pointer)) {
    this.data[pointer] = value;
    return this.getData();
  }

  let data = this.getData();
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
      if (+key === key) {
        if (!Array.isArray(partial)) {
          return data;
        }
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

JsonFinger.prototype.unset = function (pointer) {
  if (isset(this.data, pointer)) {
    if (+pointer === pointer) {
      if (Array.isArray(this.data)) {
        this.data.splice(pointer, 1);
      }
    } else {
      delete this.data[pointer];
    }

    return this.getData();
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
      return isset(parent, key);
  }
};

export default JsonFinger;
