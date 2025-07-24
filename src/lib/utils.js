export function debounce(fn, ms = 500) {
  let timeout;

  return (...args) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => fn(...args), ms);
  };
}

export function validateUrl(url, base = false) {
  try {
    url = new URL(url);
  } catch {
    return false;
  }

  if (base === true && (url.hash !== "" || url.search !== "")) {
    return false;
  }

  if (/[^-a-zA-Z0-9\._\+]+/.test(url.hostname)) {
    return false;
  }

  return url.protocol === "http:" || url.protocol === "https:";
}

export function validateBackend(data) {
  if (!data?.name) return false;

  let isValid = validateUrl(data.base_url) && Array.isArray(data.headers);
  if (!isValid) return false;

  const contentType = data.headers.find(
    ({ name }) => name === "Content-Type"
  )?.value;

  if (!contentType) {
    return false;
  }

  if (data.authentication?.type) {
    isValid = isValid && data.authentication.client_secret;

    if (data.authentication.type !== "Bearer") {
      isValid = isValid && data.authentication.client_id;
    }
  }

  return isValid;
}

export function sortByNamesOrder(items, order) {
  return items.sort((a, b) => {
    if (!order.includes(a.name)) {
      return 1;
    } else if (!order.includes(b.name)) {
      return -1;
    } else {
      return order.indexOf(a.name) - order.indexOf(b.name);
    }
  });
}

export function prependEmptyOption(options) {
  if (options[0]?.value === "") return options;
  return [{ label: "", value: "" }].concat(options);
}

export function downloadJson(data, fileName = "forms-bridge-export") {
  const json = JSON.stringify(data);
  const blob = new Blob([json], { type: "application/json" });
  const url = URL.createObjectURL(blob);

  const anchor = document.createElement("a");
  anchor.href = url;
  anchor.download = fileName + ".json";

  document.body.appendChild(anchor);
  anchor.click();
  document.body.removeChild(anchor);
}

export function uploadJson() {
  return new Promise((res, rej) => {
    const input = document.createElement("input");
    input.type = "file";
    input.accept = "application/json";

    input.addEventListener("cancel", function () {
      document.body.removeChild(input);
      rej();
    });

    input.addEventListener("change", function () {
      if (input.files.length === 1) {
        const reader = new FileReader();

        reader.onerror = function (err) {
          document.body.removeChild(input);
          rej(err);
        };

        reader.onload = function () {
          let data;
          try {
            data = JSON.parse(reader.result);
            res(data);
          } catch (err) {
            rej(err);
          } finally {
            document.body.removeChild(input);
          }
        };

        reader.readAsText(input.files[0]);
      } else {
        document.body.removeChild(input);
      }
    });

    document.body.appendChild(input);
    input.click();
  });
}

export function isset(obj, attr) {
  if (!obj || typeof obj !== "object") {
    return false;
  }

  if (Array.isArray(obj)) {
    return obj.length > attr;
  }

  return Object.prototype.hasOwnProperty.call(obj, attr);
}

export function adminUrl(path = "", query = {}) {
  const url = new URL(wpApiSettings.root.replace(/wp-json/, "wp-admin"));
  url.pathname += path.replace(/^\/+/, "");
  url.search = new URLSearchParams(query).toString();
  return url.toString();
}

export function restUrl(path = "", query = {}) {
  const url = new URL(wpApiSettings.root);
  url.pathname += path.replace(/^\/+/, "");
  url.search = new URLSearchParams(query).toString();
  return url.toString();
}
