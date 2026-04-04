import eslint from '@eslint/js';
import stylistic from '@stylistic/eslint-plugin';
import vitest from '@vitest/eslint-plugin';
import { dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import tseslint from 'typescript-eslint';
import vue from 'eslint-plugin-vue';

const configDir = dirname(fileURLToPath(import.meta.url));

export default tseslint.config(
    {
        ignores: [
            '.nuxt/**',
            '.output/**',
            'coverage/**',
            'node_modules/**',
            'test-results/**',
        ],
    },
    {
        files: ['**/*.ts', '**/*.vue'],

        extends: [
            eslint.configs.recommended,
            ...tseslint.configs.recommended,
            ...vue.configs['flat/recommended'],
        ],

        plugins: { '@stylistic': stylistic },

        languageOptions: {
            ecmaVersion: 'latest',
            parserOptions: {
                parser: tseslint.parser,
            },
        },

        rules: {
            'no-undef': 'off',
            curly: ['error', 'all'],
            eqeqeq: ['error', 'always'],
            camelcase: ['error'],
            quotes: ['error', 'single', { avoidEscape: true, allowTemplateLiterals: true }],
            'no-console': ['warn', { allow: ['warn', 'error'] }],
            'no-unused-vars': 'off',
            'no-useless-escape': ['error'],

            '@stylistic/brace-style': ['error', '1tbs'],
            '@stylistic/semi': ['error', 'always'],
            '@stylistic/indent': ['error', 4],
            '@stylistic/member-delimiter-style': ['error', {
                multiline: {
                    delimiter: 'comma',
                    requireLast: true,
                },
                singleline: {
                    delimiter: 'semi',
                    requireLast: false,
                },
            }],
            '@stylistic/space-infix-ops': 'off',
            '@stylistic/no-multi-spaces': ['error'],
            '@stylistic/object-curly-spacing': ['error', 'always'],
            '@stylistic/space-before-function-paren': ['error', {
                anonymous: 'always',
                named: 'never',
                asyncArrow: 'always',
            }],
            '@stylistic/spaced-comment': ['error', 'always'],
            '@stylistic/no-tabs': ['error'],
            '@stylistic/no-mixed-spaces-and-tabs': ['error'],
            '@stylistic/max-len': 'off',
            '@stylistic/quote-props': ['error', 'as-needed'],
            '@stylistic/no-extra-semi': ['error'],
            '@stylistic/comma-dangle': ['error', 'always-multiline'],

            '@typescript-eslint/no-unused-vars': ['warn', { argsIgnorePattern: '^_', caughtErrorsIgnorePattern: '^_' }],
            '@typescript-eslint/explicit-function-return-type': ['error', {
                allowTypedFunctionExpressions: true,
                allowHigherOrderFunctions: true,
                allowDirectConstAssertionInArrowFunctions: true,
                allowConciseArrowFunctionExpressionsStartingWithVoid: true,
                allowFunctionsWithoutTypeParameters: true,
            }],
            '@typescript-eslint/no-inferrable-types': 0,
            '@typescript-eslint/no-explicit-any': 0,
            '@typescript-eslint/no-empty-function': 0,
            '@typescript-eslint/no-non-null-assertion': 0,
            '@typescript-eslint/prefer-for-of': 'error',
            '@typescript-eslint/consistent-type-imports': ['error', {
                prefer: 'type-imports',
                disallowTypeAnnotations: true,
                fixStyle: 'separate-type-imports',
            }],

            'vue/order-in-components': ['error', {
                order: [
                    'el', 'name', 'parent', 'functional',
                    ['template', 'render'], 'inheritAttrs',
                    ['provide', 'inject'], 'extends',
                    'mixins', 'model', ['components', 'directives', 'filters'],
                    'emits', ['props', 'propsData'], ['setup', 'created'], 'data',
                    'metaInfo', 'computed', 'watch', 'LIFECYCLE_HOOKS',
                    'methods', ['delimiters', 'comments'],
                    'renderError',
                ],
            }],
            'vue/max-attributes-per-line': ['error', { singleline: 3 }],
            'vue/component-definition-name-casing': ['error', 'kebab-case'],
            'vue/multi-word-component-names': 'off',
            'vue/no-mutating-props': 'off',
            'vue/require-explicit-emits': ['error'],
            'vue/block-lang': ['error', { script: { lang: 'ts' } }],
            'vue/html-indent': ['error', 4, { baseIndent: 0 }],
            'vue/html-quotes': ['error', 'single', { avoidEscape: true }],
            'vue/html-closing-bracket-newline': ['error', { singleline: 'never', multiline: 'always' }],
            'vue/block-order': ['error', {
                order: [['template', 'script[setup]', 'script:not([setup])', 'style']],
            }],
        },
    },
    {
        files: ['tests/**/*.ts'],

        plugins: { vitest },

        languageOptions: {
            ecmaVersion: 'latest',
            parserOptions: {
                parser: tseslint.parser,
                projectService: true,
                tsconfigRootDir: configDir,
            },
        },

        rules: {
            ...vitest.configs.recommended.rules,
            camelcase: 'off',
            'vue/one-component-per-file': 'off',
        },

        settings: {
            vitest: {
                typecheck: true,
            },
        },
    },
);
