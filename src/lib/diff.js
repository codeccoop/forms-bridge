import { isset } from "./utils";

export default function (to, from, throwError = true) {
  try {
    const changes = diff(to, from, throwError);
    return count(changes) > 0;
  } catch (err) {
    // If error, there are something wrong with the state, isn't? Better to reload.
    return true;
  }
}

function isDate(x) {
  return Object.prototype.toString.call(x) === "[object Date]";
}

function isArray(x) {
  return Object.prototype.toString.call(x) === "[object Array]";
}

function isObject(x) {
  return Object.prototype.toString.call(x) === "[object Object]";
}

function isValue(x) {
  return !isObject(x) && !isArray(x);
}

function typeOf(x) {
  if (isObject(x)) {
    return "object";
  } else if (isDate(x)) {
    return "date";
  } else if (isArray(x)) {
    return "array";
  } else if (isValue(x)) {
    return "value";
  }
}

function diff(to, from, changes = {}, throwError = true) {
  changes = getChanges(to, from, changes, throwError);
  changes = getChanges(from, to, changes, throwError);
  return changes;
}

function getChanges(to, from, changes, throwError) {
  for (const k in to) {
    if (isset(changes, k)) {
      continue;
    }

    if (!isset(from, k)) {
      if (throwError) throw "change";
      changes[k] = true;
      continue;
    }

    const tt = typeOf(to[k]);
    const ft = typeOf(from[k]);

    if (!tt || !ft) {
      throw "Invalid argument: Only serializable data can be diffed";
    }

    if (tt !== ft) {
      if (throwError) throw "change";
      changes[k] = true;
      continue;
    }

    if (tt === "object" || tt === "array") {
      changes = { ...changes, [k]: diff(to[k], from[k], {}, throwError) };
    } else if (to[k] !== from[k]) {
      if (throwError) throw "change";
      changes[k] = true;
    } else {
      changes[k] = false;
    }
  }

  return changes;
}

function count(changes, n = 0) {
  return Object.keys(changes).reduce((n, k) => {
    if (typeOf(changes[k]) === "object") {
      return count(changes[k], n);
    } else if (changes[k]) {
      return n + 1;
    } else {
      return n;
    }
  }, n);
}
