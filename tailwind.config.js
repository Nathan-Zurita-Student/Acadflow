/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/views/**/*.blade.php',
    './resources/js/**/*.{vue,js,ts,jsx,tsx}',
  ],
  theme: {
    extend: {
      colors: {
        // Uses CSS custom properties so all 5 themes work without touching components.
        // Variables hold space-separated RGB channels to support opacity modifiers (e.g. bg-dark-800/50).
        dark: {
          50:  'rgb(var(--d-50)  / <alpha-value>)',
          100: 'rgb(var(--d-100) / <alpha-value>)',
          200: 'rgb(var(--d-200) / <alpha-value>)',
          300: 'rgb(var(--d-300) / <alpha-value>)',
          400: 'rgb(var(--d-400) / <alpha-value>)',
          500: 'rgb(var(--d-500) / <alpha-value>)',
          600: 'rgb(var(--d-600) / <alpha-value>)',
          700: 'rgb(var(--d-700) / <alpha-value>)',
          750: 'rgb(var(--d-750) / <alpha-value>)',
          800: 'rgb(var(--d-800) / <alpha-value>)',
          900: 'rgb(var(--d-900) / <alpha-value>)',
          950: 'rgb(var(--d-950) / <alpha-value>)',
        },
      },
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
      animation: {
        'fade-in':    'fadeIn 0.2s ease-out',
        'slide-in':   'slideIn 0.25s ease-out',
        'slide-up':   'slideUp 0.25s ease-out',
        'slide-right':'slideRight 0.3s ease-out',
        'slide-left': 'slideLeft 0.3s ease-out',
      },
      keyframes: {
        fadeIn: {
          from: { opacity: '0' },
          to:   { opacity: '1' },
        },
        slideIn: {
          from: { opacity: '0', transform: 'translateX(-8px)' },
          to:   { opacity: '1', transform: 'translateX(0)' },
        },
        slideUp: {
          from: { opacity: '0', transform: 'translateY(8px)' },
          to:   { opacity: '1', transform: 'translateY(0)' },
        },
        slideRight: {
          from: { opacity: '0', transform: 'translateX(24px)' },
          to:   { opacity: '1', transform: 'translateX(0)' },
        },
        slideLeft: {
          from: { opacity: '0', transform: 'translateX(-24px)' },
          to:   { opacity: '1', transform: 'translateX(0)' },
        },
      },
    },
  },
  plugins: [],
}
