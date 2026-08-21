import { build } from 'vite';
import configs from '../vite.config.js';

for (const config of configs) {
  // Sequential, not parallel: each build's emptyOutDir must run in order.
  await build({ ...config, configFile: false });
}
