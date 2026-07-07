/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/views/**/*.blade.php',
    './resources/js/**/*.{vue,js,ts,jsx,tsx}',
  ],
  safelist: [
    { pattern: /^(bg|text|border|ring|fill|from|to)-(accent)-(300|400|500|600|700)$/, variants: ['hover', 'focus', 'focus-within', 'group-hover'] },
    { pattern: /^(bg|text|border|ring)-(accent)-(300|400|500|600|700)\/(10|20|30)$/, variants: ['hover'] },
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
        // Per-theme accent: buttons, active nav, focus rings, links.
        // Each theme defines --accent-300..700 in app.css.
        accent: {
          300: 'rgb(var(--accent-300) / <alpha-value>)',
          400: 'rgb(var(--accent-400) / <alpha-value>)',
          500: 'rgb(var(--accent-500) / <alpha-value>)',
          600: 'rgb(var(--accent-600) / <alpha-value>)',
          700: 'rgb(var(--accent-700) / <alpha-value>)',
        },
      },
      fontFamily: {
        // Geist é a fonte principal (premium). Inter/system permanecem como fallback.
        sans: ['Geist', 'Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        mono: ['"Geist Mono"', 'ui-monospace', 'SFMono-Regular', 'Menlo', 'monospace'],
      },
      // Easings modernos (spring/ease refinados) reutilizáveis em todo o app.
      transitionTimingFunction: {
        'spring':    'cubic-bezier(0.34, 1.56, 0.64, 1)',
        'spring-sm': 'cubic-bezier(0.22, 1.2, 0.36, 1)',
        'out-expo':  'cubic-bezier(0.16, 1, 0.3, 1)',
        'in-out-smooth': 'cubic-bezier(0.65, 0, 0.35, 1)',
      },
      boxShadow: {
        // Glows discretos em accent — profundidade "premium".
        'glow-sm':   '0 0 0 1px rgb(var(--accent-500) / 0.15), 0 4px 20px -4px rgb(var(--accent-500) / 0.25)',
        'glow':      '0 0 0 1px rgb(var(--accent-500) / 0.20), 0 8px 40px -8px rgb(var(--accent-500) / 0.45)',
        'glow-lg':   '0 0 60px -12px rgb(var(--accent-500) / 0.55)',
        'card-float':'0 24px 60px -18px rgb(0 0 0 / 0.55), 0 8px 24px -12px rgb(0 0 0 / 0.4)',
        'inner-top': 'inset 0 1px 0 0 rgb(255 255 255 / 0.06)',
      },
      backgroundImage: {
        'grid-fade': "linear-gradient(to right, rgb(var(--d-700) / 0.35) 1px, transparent 1px), linear-gradient(to bottom, rgb(var(--d-700) / 0.35) 1px, transparent 1px)",
        'sheen': 'linear-gradient(110deg, transparent 30%, rgb(255 255 255 / 0.10) 50%, transparent 70%)',
      },
      backgroundSize: {
        'grid': '44px 44px',
      },
      animation: {
        // Base (existentes) — mantidas para compatibilidade.
        'fade-in':    'fadeIn 0.2s ease-out',
        'slide-in':   'slideIn 0.25s ease-out',
        'slide-up':   'slideUp 0.25s ease-out',
        'slide-right':'slideRight 0.3s ease-out',
        'slide-left': 'slideLeft 0.3s ease-out',
        // Premium — microinterações e background vivo.
        'blur-in':    'blurIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) both',
        'scale-in':   'scaleIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) both',
        'rise':       'rise 0.6s cubic-bezier(0.16, 1, 0.3, 1) both',
        'float-slow': 'floatY 14s ease-in-out infinite',
        'float-med':  'floatY 10s ease-in-out infinite',
        'drift':      'drift 22s ease-in-out infinite',
        'drift-rev':  'driftRev 26s ease-in-out infinite',
        'aurora':     'aurora 16s ease-in-out infinite',
        'glow-pulse': 'glowPulse 4s ease-in-out infinite',
        'shimmer':    'shimmer 2.2s linear infinite',
        'spin-slow':  'spin 9s linear infinite',
        'grid-pan':   'gridPan 40s linear infinite',
        'ripple':     'ripple 0.6s ease-out forwards',
        'gradient-x': 'gradientX 6s ease infinite',
        'shake':      'shake 0.4s cubic-bezier(0.36, 0.07, 0.19, 0.97) both',
        'checkmark':  'checkmark 0.4s cubic-bezier(0.65, 0, 0.35, 1) 0.1s both',
      },
      keyframes: {
        fadeIn: { from: { opacity: '0' }, to: { opacity: '1' } },
        slideIn:    { from: { opacity: '0', transform: 'translateX(-8px)' }, to: { opacity: '1', transform: 'translateX(0)' } },
        slideUp:    { from: { opacity: '0', transform: 'translateY(8px)' },  to: { opacity: '1', transform: 'translateY(0)' } },
        slideRight: { from: { opacity: '0', transform: 'translateX(24px)' }, to: { opacity: '1', transform: 'translateX(0)' } },
        slideLeft:  { from: { opacity: '0', transform: 'translateX(-24px)' },to: { opacity: '1', transform: 'translateX(0)' } },
        blurIn:  { from: { opacity: '0', filter: 'blur(8px)', transform: 'translateY(6px)' }, to: { opacity: '1', filter: 'blur(0)', transform: 'translateY(0)' } },
        scaleIn: { from: { opacity: '0', transform: 'scale(0.94)' }, to: { opacity: '1', transform: 'scale(1)' } },
        rise:    { from: { opacity: '0', transform: 'translateY(16px)' }, to: { opacity: '1', transform: 'translateY(0)' } },
        floatY:  { '0%,100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(-14px)' } },
        drift: {
          '0%,100%': { transform: 'translate3d(0,0,0) scale(1)' },
          '33%':     { transform: 'translate3d(4%, -6%, 0) scale(1.08)' },
          '66%':     { transform: 'translate3d(-5%, 3%, 0) scale(0.96)' },
        },
        driftRev: {
          '0%,100%': { transform: 'translate3d(0,0,0) scale(1)' },
          '33%':     { transform: 'translate3d(-6%, 5%, 0) scale(1.06)' },
          '66%':     { transform: 'translate3d(5%, -4%, 0) scale(0.94)' },
        },
        aurora: {
          '0%,100%': { opacity: '0.55', transform: 'translate3d(-4%, 0, 0) rotate(0deg)' },
          '50%':     { opacity: '0.9',  transform: 'translate3d(4%, -3%, 0) rotate(6deg)' },
        },
        glowPulse: {
          '0%,100%': { opacity: '0.4', transform: 'scale(1)' },
          '50%':     { opacity: '0.8', transform: 'scale(1.06)' },
        },
        shimmer:  { '0%': { transform: 'translateX(-120%)' }, '100%': { transform: 'translateX(120%)' } },
        gridPan:  { '0%': { backgroundPosition: '0 0' }, '100%': { backgroundPosition: '44px 44px' } },
        ripple:   { '0%': { transform: 'scale(0)', opacity: '0.5' }, '100%': { transform: 'scale(2.6)', opacity: '0' } },
        gradientX:{ '0%,100%': { backgroundPosition: '0% 50%' }, '50%': { backgroundPosition: '100% 50%' } },
        shake:    { '10%,90%': { transform: 'translateX(-1px)' }, '20%,80%': { transform: 'translateX(2px)' }, '30%,50%,70%': { transform: 'translateX(-4px)' }, '40%,60%': { transform: 'translateX(4px)' } },
        checkmark:{ from: { strokeDashoffset: '24' }, to: { strokeDashoffset: '0' } },
      },
    },
  },
  plugins: [],
}
