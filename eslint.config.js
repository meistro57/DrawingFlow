import js from '@eslint/js';
import vuePlugin from 'eslint-plugin-vue';
import globals from 'globals';

/** @type {import('eslint').Linter.Config[]} */
export default [
    {
        ignores: ['public/build/**', 'vendor/**'],
    },
    {
        ...js.configs.recommended,
        files: ['resources/js/**/*.js'],
        languageOptions: {
            ...js.configs.recommended.languageOptions,
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: {
                ...globals.browser,
                ...globals.node,
            },
        },
    },
    ...vuePlugin.configs['flat/recommended'].map((config) => ({
        ...config,
        files: ['resources/js/**/*.vue'],
        languageOptions: {
            ...config.languageOptions,
            globals: {
                ...globals.browser,
                ...globals.node,
                ...(config.languageOptions?.globals ?? {}),
            },
        },
    })),
    {
        files: ['resources/js/**/*.vue'],
        rules: {
            'vue/attributes-order': 'off',
            'vue/first-attribute-linebreak': 'off',
            'vue/html-closing-bracket-newline': 'off',
            'vue/html-closing-bracket-spacing': 'off',
            'vue/html-indent': 'off',
            'vue/html-self-closing': 'off',
            'vue/max-attributes-per-line': 'off',
            'vue/multi-word-component-names': 'off',
            'vue/multiline-html-element-content-newline': 'off',
            'vue/no-v-html': 'off',
            'vue/prop-name-casing': 'off',
            'vue/require-default-prop': 'off',
            'vue/singleline-html-element-content-newline': 'off',
        },
    },
];
