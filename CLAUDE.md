# Beplus Advanced Reviews For Woocommerce — Claude Code Briefing

This file is the entry point for Claude Code. The full architecture, naming standards, and module documentation live in [`AGENTS.md`](./AGENTS.md) and [`Document Plugin.md`](./Document Plugin.md) — always read both alongside this file.

## Quick start

See [`AGENTS.md`](./AGENTS.md) for the complete reference, including:
- Plugin architecture and load map
- Naming conventions and constants
- Module registry and database schema
- Gutenberg blocks and REST API
- Quality checks and security baseline
- Accessibility requirements

## Essential commands (from plugin root)

```bash
npm install && npm run composer:install   # first-time setup
npm run build                              # compile all assets
npm run watch                              # dev mode with hot rebuild
npm run precommit                          # pre-commit dry-run
npm run prepush                            # pre-push dry-run
npm run lint:php:all                       # PHPStan analysis
npm run typecheck                          # TypeScript type checking
```
