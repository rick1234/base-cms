import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { cpSync, existsSync, mkdirSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const projectRoot = dirname(fileURLToPath(import.meta.url));

function copyFlagIcons() {
    const source = resolve(projectRoot, 'node_modules/flag-icons/flags');
    const destination = resolve(projectRoot, 'public/vendor/flag-icons/flags');

    if (! existsSync(source)) {
        return;
    }

    mkdirSync(dirname(destination), { recursive: true });
    cpSync(source, destination, { recursive: true });
}

function flagIconAssets() {
    return {
        name: 'flag-icon-assets',
        buildStart: copyFlagIcons,
        configureServer: copyFlagIcons,
    };
}

export default defineConfig({
    plugins: [
        flagIconAssets(),
        laravel({
            input: ['resources/scss/app.scss', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
