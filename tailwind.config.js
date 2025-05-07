/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./templates/**/*.html.twig", // tous vos templates Twig
    "./assets/**/*.js", // si vous avez des fichiers JS dans assets
  ],
  theme: {
    extend: {
      colors: {
        // === PRIMARY COLORS ===
        "primary-blue": "#333367",
        "primary-orange": "#ff6600",
        "primary-red": "#990f00",
        "primary-black": "#020620",

        // === SECONDARY COLORS ===
        "secondary-blue": "#6699cd",
        "secondary-orange": "#ff9966",
        "secondary-red": "#cc6766",
        "secondary-beige": "#fbecd3",
      },
      fontFamily: {
        title: ['"Anton"', "cursive"],
        body: ['"Roboto Condensed"', "sans-serif"],
      },
      inset: {
        '-35': '-35px',
      },
    },
  },
  plugins: [],
};
