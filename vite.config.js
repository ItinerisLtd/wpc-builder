import { resolve } from 'node:path';
import { defineConfig } from 'vite';

/**
 * Each JS entry is built as its own separate Rolldown invocation, in
 * `output.format: 'iife'` with `codeSplitting: false`. WordPress enqueues
 * every one of this package's dist scripts as a plain classic script (no
 * `type="module"`), so no entry may contain an `import`/`export`
 * statement, but Rolldown's code-splitting (needed for any multi-entry
 * build) always extracts a module shared by 2+ entries, such as
 * `template.js`, into a separately-imported chunk. Building one entry per
 * Rolldown invocation means there is nothing to split: each entry's own
 * copy of any shared module's code is inlined directly into it. This file
 * default-exports an array of configs, so it is not loadable via a direct
 * `npx vite build` invocation. It is only consumed via
 * `scripts/vite-build-all.mjs`.
 *
 * Entries may be { name, src } instead of a bare string when an entry's
 * dist output name (hardcoded elsewhere via
 * Support\Asset::version()) must stay stable independent of where
 * its source file actually lives.
 */
const jsEntries = [
  'dependencies',
  'required-when',
  'repeater',
  'select',
  'image',
  { name: 'editor', src: 'wp-core-style/editor' },
  { name: 'multicheck', src: 'wp-core-style/multicheck' },
  'slider',
  'url-validation',
  'url',
  'link',
];

const jsConfigs = jsEntries.map((entry, index) => {
  const name = 'string' === typeof entry ? entry : entry.name;
  const src = 'string' === typeof entry ? entry : entry.src;

  return defineConfig({
    build: {
      emptyOutDir: 0 === index,
      minify: true,
      rollupOptions: {
        input: { [`js/${name}`]: resolve(`assets/src/js/${src}.js`) },
        output: {
          format: 'iife',
          codeSplitting: false,
          entryFileNames: '[name].js',
        },
      },
    },
  });
});

const cssConfig = defineConfig({
  build: {
    emptyOutDir: false,
    minify: true,
    rollupOptions: {
      input: {
        'css/controls': resolve('assets/src/css/controls.css'),
        'css/repeater': resolve('assets/src/css/repeater.css'),
        'css/toggle': resolve('assets/src/css/toggle.css'),
        'css/link': resolve('assets/src/css/link.css'),
        'css/required-when': resolve('assets/src/css/required-when.css'),
      },
      output: {
        assetFileNames: '[name][extname]',
      },
    },
  },
});

export default [...jsConfigs, cssConfig];
