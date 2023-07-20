/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './templates/**/*.{html,twig}',
    './*.theme',
  ],
  theme: {
    colors: {
      // Using modern `rgb`
      // Using legacy `rgba`
      transparent: 'transparent',
      primary: 'rgba(var(--color-primary), 1)',
      secondary: 'rgba(var(--color-secondary), 1)',
      'dd-gray': 'rgba(var(--color-dd-gray), 1)',
      'dd-gray-dark': 'rgba(var(--color-dd-gray-dark), 1)',
    },
    container: {
      center: true,
      padding: 'var(--gap-base--desktop)',
      screens: {
        '2xl': '1300px',
        'xl': '1300px',
      },
    },
    extend: {},
  },
  corePlugins: {
    container: false
  },
  plugins: [
    require('tailwind-bootstrap-grid')({
      gridGutterWidth: 'var(--gap-base--col--desktop)',
      containerMaxWidths: {
        '2xl': '1280px',
      },
    }),
    require('@tailwindcss/forms')({
      strategy: 'base',
    }),
  ],
}

