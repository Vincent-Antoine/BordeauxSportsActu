const Encore = require("@symfony/webpack-encore");

Encore
  // le répertoire où sera stocké le build final
  .setOutputPath("public/build/")
  // le chemin public utilisé par le serveur web pour accéder aux assets
  .setPublicPath("/build")
  // votre entrée principale
  .addEntry("app", "./assets/app.js")
  // Active le PostCSS (où Tailwind sera intégré)
  .enablePostCssLoader()
  .splitEntryChunks()
  .enableSingleRuntimeChunk()
  .cleanupOutputBeforeBuild()
  .enableSourceMaps(!Encore.isProduction())
  .enableVersioning(Encore.isProduction());

module.exports = Encore.getWebpackConfig();
