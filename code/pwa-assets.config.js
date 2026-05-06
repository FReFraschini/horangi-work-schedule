import { defineConfig, minimalPreset } from '@vite-pwa/assets-generator/config';

export default defineConfig({
    preset: {
        ...minimalPreset,
        maskable: {
            sizes: [512],
            padding: 0.15,
            resizeOptions: { background: '#1A73E8' },
        },
        apple: {
            sizes: [180],
            padding: 0.1,
            resizeOptions: { background: '#1A73E8' },
        },
        favicon: {
            sizes: [64, 48, 32, 16],
        },
    },
    images: ['public/logo.svg'],
});
