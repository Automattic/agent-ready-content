# PRC Bridge

PRC Bridge is a transitional, separately loaded WordPress plugin that connects
existing PRC Markdown for Agents integrations to Agent Ready Content on
WordPress VIP. It requires WordPress 6.8 or newer and PHP 8.2 or newer.

This directory is maintained as an integration example, not as part of the
Agent Ready Content plugin. It is excluded from the base plugin's release ZIP.

## Table of contents

- [Installation](#installation)
- [Create a release ZIP](#create-a-release-zip)
- [How the bridge loads](#how-the-bridge-loads)
- [What the bridge supplies](#what-the-bridge-supplies)
- [Compatibility and testing](#compatibility-and-testing)
- [Moving providers off the bridge](#moving-providers-off-the-bridge)

## Installation

Copy `prc-bridge` into the application's WordPress plugins directory. On
WordPress VIP, follow the documentation for
[activating plugins through code](https://docs.wpvip.com/how-tos/activate-plugins-through-code/).
Load Agent Ready Content first, followed by PRC Bridge and then the required PRC
providers.

Disable PRC Markdown for Agents. Some providers check for its classes while
loading their main files, so the bridge must already be loaded. Providers still
need their own dependencies and service configuration. The bridge does not
supply PRC Scripts, Firebase, PRC Components, or any other provider dependency.
It has no Composer or JavaScript runtime dependencies.

## Create a release ZIP

From the `integrations/prc-bridge` directory, install the development dependency
and build the standalone `prc-bridge.zip` archive:

```sh
npm install
npm run plugin-zip
```

The archive contains a top-level `prc-bridge` directory and can be installed as
a separate WordPress plugin. The bridge's npm dependency is development-only
and is not included in the archive.

## How the bridge loads

The plugin entry point registers its autoloader and legacy PRC class aliases as
soon as the file is included. This allows provider entry points that check for
PRC Markdown for Agents classes while loading to continue normally.

At `plugins_loaded` priority 5, the bridge registers its hook translations and
provider adapters. Agent Ready Content initializes at priority 10. Applications
must still include the plugin files in the order described above because
`plugins_loaded` cannot help a provider whose entry point has already stopped
loading after a failed class check.

## What the bridge supplies

- Legacy PRC class names for the shared block registry, resolver, converters,
  frontmatter, index, response, and cache invalidators. It does not recreate the
  old bootstrap, settings screen, or REST endpoints.
- Translation of PRC registration and context actions, document filters,
  registered per-block filters, block metadata, and post type support flags.
- Adapters for PRC staff authors, dataset links, PDF extraction links, report
  contents, and next-chapter links.
- Read-only fallback to saved PRC settings and Content-Signal options. Saved
  Agent Ready Content fields take precedence, including empty lists.
  `featured_reports` maps to `featured_posts`. The bridge neither copies nor
  deletes options and does not add Pew-branded defaults.
- VIP purges for the PDF provider's parent-based `/extraction` and `/text`
  routes, including former parent URLs before moves. Extraction metadata changes
  also clear the extraction's Markdown document cache.

## Compatibility and testing

This bridge supports the PRC provider contracts present at the commits below. It
does not recreate every internal class or historical REST route from PRC
Markdown for Agents. Automated compatibility coverage is in
[`PRCBridgeCompatibilityTest.php`](../../tests/integration/PRCBridgeCompatibilityTest.php)
and runs with `npm run test:integration` from the repository root.

Manual testing used WordPress 7.1, PHP 8.2.33, and a WordPress VIP development
environment. The last tested commit for each provider was:

| Plugin                    | Last tested commit |
| ------------------------- | ------------------ |
| prc-staff-bylines         | `1beca55`          |
| prc-datasets              | `f9a969e`          |
| prc-pdf-extraction        | `f66b91a`          |
| prc-report-package        | `c45f1b9`          |
| prc-content-transformer   | `2fc3b0a`          |
| prc-chart-builder         | `3e73be6`          |
| prc-block-library         | `4b3928a`          |
| prc-post-publish-pipeline | `f8bce15`          |
| prc-block-tables          | `904c418`          |

Still to verify:

- Cache invalidation for staff profile and byline changes, dataset assignments,
  synced chart edits, report ordering, and extraction parent edits after caches
  are warm.
- PDF route access for private, draft, and password-protected parents, plus
  `/text` responses and deletion behavior.
- Current provider editor workflows and broader block coverage, including synced
  charts, tabs, accordions, timelines, and provider metadata callbacks.
- Content Transformer AI operations and PDF OCR with configured providers.
- Provider-side PHP 8.2 build reproducibility and runtime warnings.
- Plugin load order and edge-cache purges in the deployed VIP application.

## Moving providers off the bridge

New integrations should use Agent Ready Content's native extension points. To
remove a provider's bridge dependency:

1. Replace `PRC\Platform\Markdown_For_Agents` class references with
   `Agent_Ready_Content` equivalents.
2. Replace `prc_markdown_for_agents_*` hooks with
   `agent_ready_content_*` hooks.
3. Use `agentReadyContent` block metadata and the `agent-ready-content` and
   `agent-ready-content-llms-txt` post type support flags.
4. Move the relevant adapter and invalidation logic into the provider.
5. Deliberately migrate or retire saved PRC settings before removing the bridge.

See [NOTICE.md](NOTICE.md) for original authorship and [LICENSE](LICENSE) for the
license terms.
