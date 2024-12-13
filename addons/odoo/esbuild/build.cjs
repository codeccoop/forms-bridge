const esbuild = require("esbuild");

(async () => {
  await esbuild.build({
    entryPoints: ["src/index.jsx"],
    bundle: true,
    minify: true,
    outfile: "assets/addon.bundle.js",
    plugins: [
      {
        name: "rebuild-log",
        setup({ onStart, onEnd }) {
          var t;
          onStart(() => {
            t = Date.now();
          });
          onEnd(() => {
            console.log("build finished in", Date.now() - t, "ms");
          });
        },
      },
    ],
  });
})();
