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
      primary: 'var(--color-primary)',
      secondary: 'var(--color-secondary)',
      'dd-gray': 'var(--color-dd-gray)',
      'dd-gray-dark': 'var(--color-dd-gray-dark)',
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

