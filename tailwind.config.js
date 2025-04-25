const defaultTheme = require('tailwindcss/defaultTheme');

module.exports = {
  darkMode: ['selector', '[data-mode="dark"]'],
  content: [
      "./resources/**/*.blade.php",
      "./resources/**/*.js",
      "./resources/**/*.vue",
      "./resources/**/*.ts",
      "./resources/**/*.jsx",
      "./resources/**/*.tsx",
    ],
    theme: {
      extend: {},
      screens: {
          'xs': '480px',
          ...defaultTheme.screens,
      },
    },
    plugins: [
        require('daisyui')
    ],
}
