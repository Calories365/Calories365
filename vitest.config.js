import { defineConfig } from "vitest/config";
import path from "path";

// Separate from vite.config.js: the Laravel plugin there serves the app, the
// tests only need the "@" alias the sources import with.
export default defineConfig({
    resolve: {
        alias: {
            "@": path.resolve(__dirname, "resources/js/src"),
        },
    },
    test: {
        include: ["tests/js/**/*.test.js"],
    },
});
