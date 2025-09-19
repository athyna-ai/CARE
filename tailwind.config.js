/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./**/*.php",
    "./partials/**/*.php",
    "./**/*.html",
    "./**/*.js",
    "./**/*.blade.php"
  ],
  theme: {
    extend: {
      colors: {
        clinic: {
          tea: '#c8d69b',
          vanilla: '#f6e6a5',
          blue: '#3971b8',
          ivory: '#fbfcee',
          dark: '#343b1b'
        },
        brand: {
          light: '#c8d69b',
          DEFAULT: '#3971b8',
          dark: '#343b1b'
        },
        accent: {
          yellow: '#f6e6a5',
          red: '#ef4444'
        }
      },
      fontFamily: {
        'poppins': ['Poppins', 'sans-serif'],
        'comfortaa': ['Comfortaa', 'sans-serif']
      }
    }
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography')
  ]
}
