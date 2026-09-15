import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    // './storage/framework/views/*.php' used to be listed here. That directory is the
    // compiled Blade cache, so the generated CSS depended on which pages happened to
    // have been rendered since the last `view:clear` — a cold CI build produced a
    // different, smaller stylesheet than a local one. Scanning the Blade sources alone
    // is deterministic and covers everything the app actually uses now that all
    // pagination goes through <x-pagination> rather than Laravel's default view.
    content: [
        './resources/views/**/*.blade.php',
    ],

   theme: {
        extend: {
            colors: {
                // Hum 'brand' naam ka color define kar rahe hain
                brand: {
                    50: '#fafafa',
                    100: '#f4f4f5',
                    200: '#e4e4e7',
                    500: '#71717a',
                    700: '#3f3f46',
                    800: '#27272a',
                    900: '#18181b', // Hamara Primary Black/Zinc
                },
                accent: {
                    50: '#ecfdf5',
                    600: '#059669', // Hamara Emerald Accent
                }
            },
            fontFamily: {
                sans: ['Inter', 'sans-serif'], // Professional Font
            },
        },

    },

    plugins: [require('@tailwindcss/forms')],
};
