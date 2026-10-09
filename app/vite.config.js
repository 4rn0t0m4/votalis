import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        // En Docker, Vite écoute sur 0.0.0.0 mais le navigateur le joint via localhost.
        // Port 5174 par défaut : 5173 est souvent pris sur l'hôte par un autre projet.
        port: Number(process.env.VITE_PORT ?? 5174),
        strictPort: true,
        hmr: {
            host: 'localhost',
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
