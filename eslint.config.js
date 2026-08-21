import js from '@eslint/js'
import stylistic from '@stylistic/eslint-plugin'
import globals from 'globals'

export default [
  js.configs.recommended,
  {
    files: ['assets/src/js/**/*.js'],
    plugins: {
      '@stylistic': stylistic,
    },
    languageOptions: {
      ecmaVersion: 2022,
      sourceType: 'module',
      globals: {
        ...globals.browser,
      },
    },
    rules: {
      '@stylistic/quotes': ['error', 'single', { avoidEscape: true }],
      '@stylistic/semi': ['error', 'never'],
      'no-unused-vars': ['error', { argsIgnorePattern: '^_', caughtErrors: 'none' }],
    },
  },
  {
    // Follows WordPress core's own JS style (semicolons) rather than
    // this project's usual convention.
    files: ['assets/src/js/wp-core-style/**/*.js'],
    rules: {
      '@stylistic/semi': 'off',
    },
  },
  {
    files: ['assets/src/js/**/*.test.js'],
    languageOptions: {
      globals: {
        ...globals.node,
      },
    },
    rules: {
      'no-sparse-arrays': 'off',
    },
  },
]
