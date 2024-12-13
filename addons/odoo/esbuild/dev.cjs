const esbuild = require("esbuild");

(async () => {
  const ctx = await esbuild.context({
    entryPoints: ["src/index.jsx"],
    bundle: true,
    sourcemap: true,
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

  await ctx.watch();
  console.log("watching...");
})();
