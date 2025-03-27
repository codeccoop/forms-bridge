const cache = new Map();

function JsonFinger(data) {
  this.data = data;
  this.proxy = new Proxy(this, {
    get(_, attr) {
      if (Object.prototype.hasOwnProperty.call(this.data, attr)) {
        return data[attr];
      }
    },
    set(_, attr, val) {
      this.data[attr] = val;
    },
  });
}

JsonFinger.parse = function (finger) {
  if (cache.has(finger)) {
    return cache.get(finger);
  }

  const len = finger.length;
  const keys = [];
  let key = "";
  let closured = false;
  let index = 0;

  for (let i = 0; i < len; i++) {
    const char = finger[i];
    if (closured) {
      if (char === '"') {
        closured = false;
      } else {
        key += char;
      }
    } else {
      if (char === '"') {
        closured = true;
      } else if (char === ".") {
        keys.push(key);
        key = "";
      } else if (char === "[") {
        keys.push(key);
        key = "";

        i = from = i + 1;
        index = "";
        while (finger[i] !== "]" && i < len) {
          index += finger[i];
          i += 1;
        }

        if (index.length === 0) {
          index = -1;
        } else if (isNaN(index)) {
          cache.set(finger, [finger]);
          return [finger];
        }

        index = +index;
        keys.push(index);

        i += 1;
        if (finger.length > i) {
          if (finger[i] !== ".") {
            cache.set(finger, [finger]);
            return [finger];
          }
        }
      } else {
        key += char;
      }
    }
  }

  if (key) {
    keys.push(key);
  }

  cache.set(finger, keys);
  return keys;
};

JsonFinger.build = function (keys) {
  return keys
    .reduce((finger, key) => {
      const isArray = +key === key;
      if (isArray) {
        if (key === -1) {
          key = "";
        }

        key = `[${key}]`;
      } else {
        key = "." + key;
      }

      return finger + key;
    }, "")
    .slice(1);
};

JsonFinger.prototype.get = function (finger) {
  if (this[finger]) {
    return this[finger];
  }

  let value = null;
  try {
    const keys = this.parse(finger);

    value = this.data;
    for (const key of keys) {
      if (!this.has(value, key)) {
        return;
      }

      value = value[key];
    }
  } catch {
    return null;
  }
};

JsonFinger.prototype.has = function (obj, attr) {
  return Object.prototype.hasOwnProperty.call(obj, attr);
};

JsonFinger.prototype.set = function (finger, value, unset = false) {
  if (this[finger]) {
    this[finger] = value;
  }

  let data = this.data;
  const breadcrumb = [];
  try {
    const keys = this.parse(finger);
    let partial = data;

    for (let i = 0; i < keys.length - 1; i++) {
      if (!partial || typeof partial !== "object") {
        return;
      }

      let key = keys[i];
      if (key === -1 && Array.isArray(partial)) {
        if (keys.length - 1 === i) {
          key = partial.length - 1;
        } else {
          return;
        }
      }

      if (this.has(partial, key)) {
        const isArray = +key === key;
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
      if (key === -1 && Array.isArray(partial)) {
        partial.pop();
      } else if (Array.isArray(partial)) {
        delete partial[key];
      }

      for (let i = breadcrumb.length - 1; i >= 0; i--) {
        const step = breadcrumb[i];
        partial = step.partial;
        key = step.key;

        if (!this.has(partial, key)) {
          break;
        }

        delete partial[key];
      }
    } else {
      if (key === -1 && Array.isArray(partial)) {
        partial.push(value);
      } else {
        partial[key] = value;
      }
    }
  } catch (Error) {
    return this.data;
  }

  this.data = data;
  return data;
};

export default JsonFinger;
