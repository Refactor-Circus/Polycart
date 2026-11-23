// Writes the SHA-256 of ../openapi.json to openapi.sha256, so the PHP suite
// (tests/Feature/OpenApiTest.php, which runs without Node) can tell whether
// src/schema.d.ts was generated from the committed document.
import { createHash } from 'node:crypto';
import { readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const client = join(dirname(fileURLToPath(import.meta.url)), '..');
const hash = createHash('sha256').update(readFileSync(join(client, '..', 'openapi.json'))).digest('hex');

writeFileSync(join(client, 'openapi.sha256'), `${hash}\n`);
