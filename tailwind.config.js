/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./assets/**/*.js",
    "./templates/**/*.html.twig",
    "./node_modules/flowbite/**/*.js"
  ],
  theme: {
    fontFamily: {
      'itim':        ['Itim'],
      'handwritten': ['Just Another Hand']
    },
    extend: {},
    borderWidth: {
      DEFAULT: '1px',
      '0': '0',
      '0.5': '0.5px',
      '2': '2px',
      '3': '3px',
      '4': '4px',
      '6': '6px',
      '8': '8px',
      '10': '10px',
      '12': '12px'
    }
  },
  plugins: [
    require('flowbite/plugin')
  ],
}
