/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./src/**/*.{ts,html,vue}'],
  theme: {
    extend: {},
  },
  plugins: [require('daisyui')],
};
