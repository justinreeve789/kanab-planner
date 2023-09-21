module.exports = {
    content: [
      "./resources/**/*.blade.php",
      "./resources/**/*.js",
      "./resources/**/*.vue",
      "./resources/**/*.ts",
      "./resources/**/*.jsx",
      "./resources/**/*.tsx",
      "./node_modules/flowbite/**/*.js"
    ],
    theme: {
      extend: {},
    },
    plugins: [
        require('flowbite/plugin')
    ],
  }
