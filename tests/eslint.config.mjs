import eslint from '@eslint/js';
import stylistic from '@stylistic/eslint-plugin';
import tseslint from 'typescript-eslint';

export default tseslint.config(
    {
        ignores: [
            '.auth/**',
            '.cache/**',
            'node_modules/**',
            'playwright-report/**',
            'snapshots/**',
            'test-results/**',
        ],
    },
    {
        files: ['**/*.ts'],

        extends: [
            eslint.configs.recommended,
            ...tseslint.configs.recommended,
        ],

        plugins: { '@stylistic': stylistic },

        languageOptions: {
            ecmaVersion: 'latest',
            parserOptions: {
                parser: tseslint.parser,
                projectService: true,
                tsconfigRootDir: import.meta.dirname,
            },
        },

        rules: {
            'no-undef': 'off',
            curly: ['error', 'all'],
            eqeqeq: ['error', 'always'],
            camelcase: 'off',
            quotes: ['error', 'single', { avoidEscape: true, allowTemplateLiterals: true }],
            'no-console': ['warn', { allow: ['warn', 'error'] }],
            'no-unused-vars': 'off',
            'no-useless-escape': ['error'],

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
        },
    },
);
