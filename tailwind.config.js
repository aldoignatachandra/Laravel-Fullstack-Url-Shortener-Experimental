import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ["Figtree", ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    DEFAULT: "#3ecf8e",
                    dark: "#00c573",
                },
                surface: {
                    black: "#0f0f0f",
                    dark: "#171717",
                    "dark-border": "#242424",
                    border: "#2e2e2e",
                    "mid-border": "#363636",
                    "light-border": "#393939",
                    charcoal: "#434343",
                    "dark-gray": "#4d4d4d",
                    "mid-gray": "#898989",
                    "light-gray": "#b4b4b4",
                    "near-white": "#efefef",
                    "off-white": "#fafafa",
                    card: "#1e1e1e",
                },
            },
            borderRadius: {
                pill: "9999px",
            },
        },
    },

    plugins: [forms],
};
