import js from '@eslint/js';
import vuePlugin from 'eslint-plugin-vue';
import globals from 'globals';

export default [
    {
        files: ['resources/js/**/*.js', 'resources/js/**/*.vue'],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: {
                ...globals.browser,
                ...globals.node,
            },
        },
        plugins: {
            vue: vuePlugin,
        },
        rules: {
            ...js.configs.recommended.rules,
            ...vuePlugin.configs['flat/recommended'].rules,
            'vue/multi-word-component-names': 'off',
        },
    },
];
