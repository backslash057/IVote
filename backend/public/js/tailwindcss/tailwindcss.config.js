tailwind.config = {
    darkMode: ['class', '[data-theme="dark"]'],
    theme: {
        extend: {
            colors: {
                primary: {
                    DEFAULT: 'var(--primary-blue)',
                    light: 'var(--primary-blue-light)',
                    dark: 'var(--primary-blue-dark)',
                },
                orange: {
                    accent: 'var(--orange-accent)',
                    'accent-light': 'var(--orange-accent-light)',
                    'accent-dark': 'var(--orange-accent-dark)',
                },
                navy: {
                    DEFAULT: 'var(--navy-blue)',
                    light: 'var(--navy-blue-light)',
                    dark: 'var(--navy-blue-dark)',
                },
                surface: 'var(--background)',
                'surface-secondary': 'var(--background-secondary)',
                fore: 'var(--foreground)',
                'fore-secondary': 'var(--foreground-secondary)',
                bordercustom: 'var(--border)',
            },
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', 'sans-serif'],
            }
        }
    }
}