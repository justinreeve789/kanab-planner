module.exports = {
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
    },
    plugins: [
        require('daisyui')
    ],
  }
